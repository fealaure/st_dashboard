<?php

declare(strict_types=1);

namespace SaveState\Releases\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SaveState\Releases\Domain\IgdbClient;
use SaveState\Releases\Domain\Release;
use SaveState\Shared\Domain\Clock;

final class IgdbApiClient implements IgdbClient
{
    private const TOKEN_CACHE_KEY = 'igdb:access_token';
    private const TOKEN_URL = 'https://id.twitch.tv/oauth2/token';
    private const API_BASE = 'https://api.igdb.com/v4';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
        private readonly Clock $clock,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
    }

    public function fetchUpcoming(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        array $platformIds,
        int $limit,
    ): array {
        if (! $this->isConfigured()) {
            $this->logger->warning('IGDB client not configured (TWITCH_CLIENT_ID/SECRET ausentes)');

            return [];
        }

        $platformsList = implode(',', array_map('intval', $platformIds));
        $fromEpoch = $from->getTimestamp();
        $untilEpoch = $until->getTimestamp();
        $batch = max(1, min($limit, 500));

        $query = <<<APIQUERY
fields name, slug, summary, hypes, url, first_release_date,
  cover.image_id,
  platforms.id, platforms.name,
  involved_companies.publisher, involved_companies.company.name;
where first_release_date >= {$fromEpoch}
  & first_release_date <= {$untilEpoch}
  & platforms = ({$platformsList});
sort hypes desc;
limit {$batch};
APIQUERY;

        $response = $this->http
            ->withHeaders([
                'Client-ID' => $this->clientId,
                'Authorization' => 'Bearer '.$this->accessToken(),
                'Accept' => 'application/json',
            ])
            ->withBody($query, 'text/plain')
            ->post(self::API_BASE.'/games');

        if ($response->failed()) {
            $this->logger->warning('IGDB /games failed', [
                'status' => $response->status(),
                'body' => substr((string) $response->body(), 0, 400),
            ]);

            return [];
        }

        $rows = $response->json() ?? [];
        $now = $this->clock->now();
        $utc = new DateTimeZone('UTC');
        $allowedPlatforms = array_flip(array_map('intval', $platformIds));

        $releases = [];
        foreach ($rows as $row) {
            if (! isset($row['id'], $row['name'], $row['first_release_date'])) {
                continue;
            }

            $platforms = [];
            foreach ($row['platforms'] ?? [] as $platform) {
                if (! isset($platform['id'], $platform['name'])) {
                    continue;
                }
                if (! isset($allowedPlatforms[(int) $platform['id']])) {
                    continue;
                }
                $platforms[] = (string) $platform['name'];
            }
            sort($platforms);

            $publishers = [];
            foreach ($row['involved_companies'] ?? [] as $ic) {
                if (! ($ic['publisher'] ?? false)) {
                    continue;
                }
                $companyName = $ic['company']['name'] ?? null;
                if ($companyName !== null) {
                    $publishers[] = (string) $companyName;
                }
            }
            $publishers = array_values(array_unique($publishers));

            $coverUrl = null;
            $imageId = $row['cover']['image_id'] ?? null;
            if (is_string($imageId) && $imageId !== '') {
                $coverUrl = sprintf('https://images.igdb.com/igdb/image/upload/t_cover_big/%s.jpg', $imageId);
            }

            $releaseDate = (new DateTimeImmutable('@'.(int) $row['first_release_date']))->setTimezone($utc);

            $releases[] = new Release(
                id: null,
                igdbId: (int) $row['id'],
                name: (string) $row['name'],
                slug: isset($row['slug']) ? (string) $row['slug'] : null,
                summary: isset($row['summary']) ? (string) $row['summary'] : null,
                coverUrl: $coverUrl,
                hype: (int) ($row['hypes'] ?? 0),
                releaseDate: $releaseDate,
                platforms: $platforms,
                publishers: $publishers,
                igdbUrl: isset($row['url']) ? (string) $row['url'] : null,
                lastSyncedAt: $now,
            );
        }

        return $releases;
    }

    private function accessToken(): string
    {
        return $this->cache->remember(self::TOKEN_CACHE_KEY, 50 * 86400, function (): string {
            $response = $this->http
                ->asForm()
                ->post(self::TOKEN_URL, [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf(
                    'Twitch OAuth (IGDB) failed (status %d): %s',
                    $response->status(),
                    substr((string) $response->body(), 0, 200),
                ));
            }

            $token = (string) ($response->json('access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Twitch OAuth returned empty access_token.');
            }

            return $token;
        });
    }

    private function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }
}
