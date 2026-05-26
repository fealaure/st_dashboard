<?php

declare(strict_types=1);

namespace SaveState\Guides\Application;

use SaveState\Guides\Domain\Guide;
use SaveState\Guides\Domain\GuideRepository;
use SaveState\Guides\Domain\GuideSource;

final class ListGuidesUseCase
{
    private const DEFAULT_MAX_AGE_HOURS = 24 * 30;

    private const HARD_CAP_MAX_AGE_HOURS = 24 * 365;

    public function __construct(private readonly GuideRepository $guides)
    {
    }

    /**
     * @return list<array{guide: Guide, source: GuideSource}>
     */
    public function execute(int $limit = 50, int $maxAgeHours = self::DEFAULT_MAX_AGE_HOURS): array
    {
        $limit = max(1, min($limit, 200));
        $maxAgeHours = max(1, min($maxAgeHours, self::HARD_CAP_MAX_AGE_HOURS));

        $result = [];
        foreach ($this->guides->listRecent($limit, $maxAgeHours) as [$guide, $source]) {
            $result[] = ['guide' => $guide, 'source' => $source];
        }

        return $result;
    }
}
