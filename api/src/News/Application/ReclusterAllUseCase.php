<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use SaveState\News\Domain\NewsRepository;

final class ReclusterAllUseCase
{
    public function __construct(
        private readonly NewsRepository $news,
        private readonly ClusterizeNewsItemUseCase $clusterize,
    ) {
    }

    public function execute(): int
    {
        $count = 0;

        foreach ($this->news->withoutCluster() as $item) {
            $this->clusterize->execute($item);
            $count++;
        }

        return $count;
    }
}
