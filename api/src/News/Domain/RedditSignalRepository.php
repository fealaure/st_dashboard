<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface RedditSignalRepository
{
    public function upsert(RedditSignal $signal): void;

    /**
     * Soma de upvotes (score) e número de comentários dos signals vinculados ao cluster.
     *
     * @return array{upvotes:int, comments:int}
     */
    public function aggregateForCluster(int $clusterId): array;
}
