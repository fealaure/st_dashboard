<?php

declare(strict_types=1);

namespace SaveState\News\Application;

final class IngestNewsResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public readonly int $sourcesProcessed,
        public readonly int $itemsFetched,
        public readonly int $itemsInserted,
        public readonly array $errors,
    ) {
    }
}
