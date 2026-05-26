<?php

declare(strict_types=1);

namespace SaveState\Guides\Application;

use Psr\Log\LoggerInterface;
use SaveState\Guides\Domain\Guide;
use SaveState\Guides\Domain\GuideRepository;
use SaveState\Guides\Domain\GuideSource;
use SaveState\Guides\Domain\GuideSourceRepository;
use SaveState\Guides\Domain\RssFetcher;
use SaveState\Shared\Domain\Clock;
use Throwable;

final class IngestGuidesUseCase
{
    public function __construct(
        private readonly GuideSourceRepository $sources,
        private readonly GuideRepository $guides,
        private readonly RssFetcher $fetcher,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(): IngestGuidesResult
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
                $this->logger->error('Guide source ingestion failed', [
                    'source' => $source->slug,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = sprintf('%s: %s', $source->slug, $e->getMessage());
            }
        }

        return new IngestGuidesResult(
            itemsFetched: $totalFetched,
            itemsInserted: $totalInserted,
            errors: $errors,
        );
    }

    /** @return array{0:int,1:int} */
    private function ingestFromSource(GuideSource $source): array
    {
        $now = $this->clock->now();
        $fetched = 0;
        $inserted = 0;

        foreach ($this->fetcher->fetch($source) as $item) {
            $fetched++;

            if ($this->guides->existsByExternalId($source->id, $item->externalId)) {
                continue;
            }

            if ($this->guides->existsByUrl($item->url)) {
                continue;
            }

            $this->guides->save(
                Guide::newlyFetched(
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

            $inserted++;
        }

        return [$fetched, $inserted];
    }
}
