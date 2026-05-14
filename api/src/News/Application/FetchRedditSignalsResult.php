<?php

declare(strict_types=1);

namespace SaveState\News\Application;

final class FetchRedditSignalsResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public readonly int $clustersProcessed,
        public readonly int $signalsCaptured,
        public readonly array $errors,
    ) {
    }
}
