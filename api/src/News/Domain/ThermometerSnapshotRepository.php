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

    /**
     * Retorna a série temporal do termômetro de um cluster nas últimas N horas,
     * mais antigos primeiro.
     *
     * @return list<array{capturedAt: DateTimeImmutable, thermometer: float}>
     */
    public function forCluster(int $clusterId, int $hoursBack): array;
}
