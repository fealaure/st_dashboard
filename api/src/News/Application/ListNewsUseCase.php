<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use DateTimeImmutable;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsCluster;

final class ListNewsUseCase
{
    private const DEFAULT_MAX_AGE_HOURS = 72;

    public function __construct(private readonly ClusterRepository $clusters)
    {
    }

    /**
     * @return list<array{
     *   cluster: NewsCluster,
     *   sources: list<array{slug:string,name:string}>,
     *   latestPublishedAt: DateTimeImmutable
     * }>
     */
    public function execute(int $limit = 50, int $maxAgeHours = self::DEFAULT_MAX_AGE_HOURS): array
    {
        $limit = max(1, min($limit, 200));
        $maxAgeHours = max(1, min($maxAgeHours, 24 * 30));

        $result = [];
        foreach ($this->clusters->recentWithSources($limit, $maxAgeHours) as [$cluster, $sources, $publishedAt]) {
            $result[] = [
                'cluster' => $cluster,
                'sources' => $sources,
                'latestPublishedAt' => $publishedAt,
            ];
        }

        return $result;
    }
}
