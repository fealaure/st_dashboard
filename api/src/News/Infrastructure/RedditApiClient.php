<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SaveState\News\Domain\RedditClient;
use SaveState\News\Domain\RedditPost;

/**
 * Cliente Reddit com duas estratégias:
 *
 * - **OAuth** (`oauth.reddit.com`) quando há credenciais no .env. Rate limit ~100 req/min.
 * - **Público** (`www.reddit.com/...json`) quando credenciais ausentes. Rate limit ~10 req/min.
 *
 * O modo público dispensa criar app no Reddit, mas exige throttle agressivo entre requests.
 */
final class RedditApiClient implements RedditClient
{
    private const TOKEN_CACHE_KEY = 'reddit:access_token';
    private const TOKEN_URL = 'https://www.reddit.com/api/v1/access_token';
    private const OAUTH_BASE = 'https://oauth.reddit.com';
    private const PUBLIC_BASE = 'https://www.reddit.com';

    /** Pausa entre chamadas no modo público pra respeitar ~10 req/min. */
    private const PUBLIC_THROTTLE_USECONDS = 1_000_000;

    private static ?float $lastPublicCallAt = null;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $username,
        private readonly string $password,
        private readonly string $userAgent,
    ) {
    }

    public function searchByUrl(string $url): array
    {
        $response = $this->oauthConfigured()
            ? $this->authed()->get(self::OAUTH_BASE.'/api/info', ['url' => $url])
            : $this->throttledPublic()->get(self::PUBLIC_BASE.'/api/info.json', ['url' => $url]);

        if ($response->failed()) {
            $this->logger->warning('Reddit /api/info failed', [
                'mode' => $this->oauthConfigured() ? 'oauth' : 'public',
                'url' => $url,
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 300),
            ]);

            return [];
        }

        $children = $response->json('data.children') ?? [];
        $posts = [];
        $utc = new DateTimeZone('UTC');

        foreach ($children as $child) {
            $data = $child['data'] ?? null;
            if (! is_array($data)) {
                continue;
            }
            if (($child['kind'] ?? null) !== 't3') {
                continue;
            }

            $postedAtTs = (int) ($data['created_utc'] ?? 0);

            $posts[] = new RedditPost(
                id: (string) ($data['id'] ?? ''),
                subreddit: (string) ($data['subreddit'] ?? ''),
                title: (string) ($data['title'] ?? ''),
                permalink: 'https://www.reddit.com'.(string) ($data['permalink'] ?? ''),
                score: (int) ($data['score'] ?? 0),
                numComments: (int) ($data['num_comments'] ?? 0),
                postedAt: (new DateTimeImmutable('@'.$postedAtTs))->setTimezone($utc),
            );
        }

        return $posts;
    }

    private function authed(): PendingRequest
    {
        return $this->http
            ->withToken($this->accessToken())
            ->withUserAgent($this->userAgent);
    }

    private function throttledPublic(): PendingRequest
    {
        $this->throttle();

        return $this->http->withUserAgent($this->userAgent);
    }

    private function throttle(): void
    {
        $now = microtime(true);
        if (self::$lastPublicCallAt !== null) {
            $elapsed = ($now - self::$lastPublicCallAt) * 1_000_000;
            if ($elapsed < self::PUBLIC_THROTTLE_USECONDS) {
                usleep((int) (self::PUBLIC_THROTTLE_USECONDS - $elapsed));
            }
        }
        self::$lastPublicCallAt = microtime(true);
    }

    private function accessToken(): string
    {
        return $this->cache->remember(self::TOKEN_CACHE_KEY, 50 * 60, function (): string {
            $response = $this->http
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->withUserAgent($this->userAgent)
                ->asForm()
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'password',
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf(
                    'Reddit OAuth failed (status %d): %s',
                    $response->status(),
                    substr((string) $response->body(), 0, 200),
                ));
            }

            $token = (string) ($response->json('access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Reddit OAuth returned empty access_token.');
            }

            return $token;
        });
    }

    private function oauthConfigured(): bool
    {
        return $this->clientId !== ''
            && $this->clientSecret !== ''
            && $this->username !== ''
            && $this->password !== '';
    }
}
