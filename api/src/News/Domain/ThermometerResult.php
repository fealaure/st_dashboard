<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

final class ThermometerResult
{
    public function __construct(
        public readonly float $score,
        public readonly float $coverageComponent,
        public readonly float $redditComponent,
        public readonly float $recencyComponent,
    ) {
    }
}
