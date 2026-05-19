<?php

declare(strict_types=1);

namespace SaveState\Releases\Application;

final class SyncReleasesResult
{
    public function __construct(
        public readonly int $fetched,
        public readonly int $upserted,
    ) {
    }
}
