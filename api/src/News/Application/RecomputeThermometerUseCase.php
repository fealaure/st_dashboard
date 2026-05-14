<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsCluster;
use SaveState\News\Domain\SourceRepository;
use SaveState\News\Domain\Thermometer;
use SaveState\News\Domain\ThermometerResult;
use SaveState\Shared\Domain\Clock;

/**
 * Recalcula o termômetro de um único cluster, retornando o resultado decomposto
 * (cobertura, reddit, recência) — útil tanto pra atualizar o cluster quanto
 * pra alimentar snapshots históricos.
 */
final class RecomputeThermometerUseCase
{
    public function __construct(
        private readonly ClusterRepository $clusters,
        private readonly SourceRepository $sources,
        private readonly Clock $clock,
    ) {
    }

    public function execute(NewsCluster $cluster): ThermometerResult
    {
        if ($cluster->id === null) {
            throw new \InvalidArgumentException('Cluster precisa estar persistido.');
        }

        $distinctSources = $this->clusters->distinctSourcesCount($cluster->id);
        $totalSources = max(1, $this->sources->countActive());
        $latestPublishedAt = $this->clusters->getLatestPublishedAt($cluster->id) ?? $cluster->lastSeenAt;
        $now = $this->clock->now();

        $result = Thermometer::compute(
            distinctSources: $distinctSources,
            totalActiveSources: $totalSources,
            redditUpvotes: $cluster->redditUpvotes,
            redditComments: $cluster->redditComments,
            latestPublishedAt: $latestPublishedAt,
            now: $now,
        );

        $this->clusters->update(
            $cluster->withRecomputedThermometer(
                newScore: $result->score,
                now: $now,
                latestPublishedAt: $latestPublishedAt,
            )
        );

        return $result;
    }
}
