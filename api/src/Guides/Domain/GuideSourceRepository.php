<?php

declare(strict_types=1);

namespace SaveState\Guides\Domain;

interface GuideSourceRepository
{
    /** @return iterable<GuideSource> */
    public function allActive(): iterable;

    public function markFetched(int $sourceId): void;
}
