<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use Psr\Log\LoggerInterface;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\ThermometerSnapshotRepository;
use SaveState\Shared\Domain\Clock;
use Throwable;

/**
 * Recalcula o termômetro de todos os clusters recentes e persiste um snapshot
 * histórico do score + componentes. Pensado pra rodar a cada hora via scheduler.
 */
final class SnapshotThermometersUseCase
{
    private const MAX_AGE_HOURS = 72;

    public function __construct(
        private readonly ClusterRepository $clusters,
        private readonly RecomputeThermometerUseCase $recompute,
        private readonly ThermometerSnapshotRepository $snapshots,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(): int
    {
        $count = 0;
        $now = $this->clock->now();

        foreach ($this->clusters->recentForSnapshot(self::MAX_AGE_HOURS) as $cluster) {
            if ($cluster->id === null) {
                continue;
            }

            try {
                $result = $this->recompute->execute($cluster);
                $this->snapshots->record(
                    clusterId: $cluster->id,
                    thermometer: $result->score,
                    coverageComponent: $result->coverageComponent,
                    redditComponent: $result->redditComponent,
                    recencyComponent: $result->recencyComponent,
                    capturedAt: $now,
                );
                $count++;
            } catch (Throwable $e) {
                $this->logger->error('Snapshot failed for cluster', [
                    'cluster_id' => $cluster->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
