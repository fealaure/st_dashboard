<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\ThermometerSnapshotModel;
use DateTimeImmutable;
use SaveState\News\Domain\ThermometerSnapshotRepository;

final class EloquentThermometerSnapshotRepository implements ThermometerSnapshotRepository
{
    public function record(
        int $clusterId,
        float $thermometer,
        float $coverageComponent,
        float $redditComponent,
        float $recencyComponent,
        DateTimeImmutable $capturedAt,
    ): void {
        ThermometerSnapshotModel::query()->create([
            'cluster_id' => $clusterId,
            'thermometer' => $thermometer,
            'coverage_component' => $coverageComponent,
            'reddit_component' => $redditComponent,
            'recency_component' => $recencyComponent,
            'captured_at' => $capturedAt,
        ]);
    }

    public function forCluster(int $clusterId, int $hoursBack): array
    {
        $cutoff = now()->subHours(max(1, $hoursBack));

        $rows = ThermometerSnapshotModel::query()
            ->where('cluster_id', $clusterId)
            ->where('captured_at', '>=', $cutoff)
            ->orderBy('captured_at')
            ->get(['thermometer', 'captured_at']);

        return $rows->map(static fn ($row) => [
            'capturedAt' => DateTimeImmutable::createFromMutable($row->captured_at->toDateTime()),
            'thermometer' => (float) $row->thermometer,
        ])->all();
    }
}
