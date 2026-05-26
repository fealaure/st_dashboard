<?php

declare(strict_types=1);

namespace SaveState\Guides\Application;

final class IngestGuidesResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public readonly int $itemsFetched,
        public readonly int $itemsInserted,
        public readonly array $errors,
    ) {
    }
}
