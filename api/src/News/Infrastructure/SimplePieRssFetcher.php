<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use Psr\Log\LoggerInterface;
use SaveState\News\Domain\FetchedItem;
use SaveState\News\Domain\RssFetcher;
use SaveState\News\Domain\Source;
use SimplePie\SimplePie;
use Throwable;

final class SimplePieRssFetcher implements RssFetcher
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $cacheDir,
        private readonly int $cacheDurationSeconds = 600,
    ) {
    }

    public function fetch(Source $source): Generator
    {
        $feed = new SimplePie();
        $feed->set_feed_url($source->rssUrl);
        $feed->enable_cache(true);
        $feed->set_cache_location($this->cacheDir);
        $feed->set_cache_duration($this->cacheDurationSeconds);
        $feed->set_useragent('SaveStateDashboard/0.1 (+https://savestate)');
        $feed->force_feed(true);

        try {
            $success = $feed->init();
        } catch (Throwable $e) {
            $this->logger->error('RSS fetch threw', [
                'source' => $source->slug,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $success) {
            $this->logger->warning('RSS feed could not be parsed', [
                'source' => $source->slug,
                'error' => $feed->error(),
            ]);

            return;
        }

        $feed->handle_content_type();
        $utc = new DateTimeZone('UTC');

        foreach ($feed->get_items() as $item) {
            $link = (string) $item->get_permalink();
            $title = (string) $item->get_title();

            if ($title === '' || $link === '') {
                continue;
            }

            $externalSeed = (string) ($item->get_id() ?: $link);
            $externalId = substr(hash('sha256', $source->slug.'|'.$externalSeed), 0, 64);

            $dateString = $item->get_date('Y-m-d H:i:s');
            $publishedAt = $dateString
                ? new DateTimeImmutable($dateString, $utc)
                : new DateTimeImmutable('now', $utc);

            $excerptRaw = (string) ($item->get_description() ?? '');
            $excerpt = $excerptRaw !== '' ? trim(strip_tags($excerptRaw)) : null;
            if ($excerpt !== null && mb_strlen($excerpt) > 500) {
                $excerpt = mb_substr($excerpt, 0, 497).'...';
            }

            $authors = $item->get_authors() ?? [];
            $author = $authors !== [] ? (string) $authors[0]->get_name() : null;

            yield new FetchedItem(
                externalId: $externalId,
                title: html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5),
                url: $link,
                excerpt: $excerpt !== null ? html_entity_decode($excerpt, ENT_QUOTES | ENT_HTML5) : null,
                author: $author,
                publishedAt: $publishedAt,
            );
        }
    }
}
