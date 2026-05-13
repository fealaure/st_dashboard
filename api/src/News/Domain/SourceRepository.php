<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface SourceRepository
{
    /** @return iterable<Source> */
    public function allActive(): iterable;

    public function markFetched(int $sourceId): void;

    public function countActive(): int;
}
