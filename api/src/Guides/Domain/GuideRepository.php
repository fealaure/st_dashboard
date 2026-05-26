<?php

declare(strict_types=1);

namespace SaveState\Guides\Domain;

interface GuideRepository
{
    public function existsByExternalId(int $sourceId, string $externalId): bool;

    public function existsByUrl(string $url): bool;

    public function save(Guide $guide): Guide;

    public function deleteOlderThan(int $days): int;

    /**
     * @return iterable<array{Guide, GuideSource}>
     */
    public function listRecent(int $limit, int $maxAgeHours): iterable;
}
