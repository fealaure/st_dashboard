<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

interface ThermometerSnapshotRepository
{
    public function record(
        int $clusterId,
        float $thermometer,
        float $coverageComponent,
        float $redditComponent,
        float $recencyComponent,
        DateTimeImmutable $capturedAt,
    ): void;
}
