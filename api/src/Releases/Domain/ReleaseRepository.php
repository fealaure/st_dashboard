<?php

declare(strict_types=1);

namespace SaveState\Releases\Domain;

interface ReleaseRepository
{
    public function upsert(Release $release): void;

    /**
     * @return list<Release>
     */
    public function upcoming(int $limit, int $daysAhead): array;

    public function deleteOlderThan(\DateTimeImmutable $cutoff): int;
}
