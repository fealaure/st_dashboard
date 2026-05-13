<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use Psr\Log\LoggerInterface;
use SaveState\News\Domain\NewsItem;
use SaveState\News\Domain\NewsRepository;
use SaveState\News\Domain\RssFetcher;
use SaveState\News\Domain\Source;
use SaveState\News\Domain\SourceRepository;
use SaveState\Shared\Domain\Clock;
use Throwable;

final class IngestNewsUseCase
{
    public function __construct(
        private readonly SourceRepository $sources,
        private readonly NewsRepository $news,
        private readonly RssFetcher $fetcher,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
        private readonly ClusterizeNewsItemUseCase $clusterize,
    ) {
    }

    public function execute(): IngestNewsResult
    {
        $totalFetched = 0;
        $totalInserted = 0;
        $errors = [];

        foreach ($this->sources->allActive() as $source) {
            try {
                [$fetched, $inserted] = $this->ingestFromSource($source);
                $totalFetched += $fetched;
                $totalInserted += $inserted;
                $this->sources->markFetched($source->id);
            } catch (Throwable $e) {
                $this->logger->error('Source ingestion failed', [
                    'source' => $source->slug,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = sprintf('%s: %s', $source->slug, $e->getMessage());
            }
        }

        return new IngestNewsResult(
            sourcesProcessed: $totalFetched > 0 ? 1 : 0,
            itemsFetched: $totalFetched,
            itemsInserted: $totalInserted,
            errors: $errors,
        );
    }

    /** @return array{0:int,1:int} */
    private function ingestFromSource(Source $source): array
    {
        $now = $this->clock->now();
        $fetched = 0;
        $inserted = 0;

        foreach ($this->fetcher->fetch($source) as $item) {
            $fetched++;

            if ($this->news->existsByExternalId($source->id, $item->externalId)) {
                continue;
            }

            $saved = $this->news->save(
                NewsItem::newlyFetched(
                    sourceId: $source->id,
                    externalId: $item->externalId,
                    title: $item->title,
                    url: $item->url,
                    excerpt: $item->excerpt,
                    author: $item->author,
                    publishedAt: $item->publishedAt,
                    fetchedAt: $now,
                )
            );

            try {
                $this->clusterize->execute($saved);
            } catch (Throwable $e) {
                $this->logger->error('Clustering failed for item', [
                    'source' => $source->slug,
                    'item_id' => $saved->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $inserted++;
        }

        return [$fetched, $inserted];
    }
}
