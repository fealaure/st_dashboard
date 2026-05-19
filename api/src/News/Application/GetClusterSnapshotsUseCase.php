<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use SaveState\News\Domain\ThermometerSnapshotRepository;

final class GetClusterSnapshotsUseCase
{
    public function __construct(private readonly ThermometerSnapshotRepository $snapshots)
    {
    }

    /**
     * @return list<array{capturedAt: \DateTimeImmutable, thermometer: float}>
     */
    public function execute(int $clusterId, int $hoursBack = 24): array
    {
        return $this->snapshots->forCluster($clusterId, max(1, min($hoursBack, 24 * 30)));
    }
}
