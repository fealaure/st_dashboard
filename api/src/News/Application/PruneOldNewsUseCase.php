<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use SaveState\News\Domain\NewsRepository;

final class PruneOldNewsUseCase
{
    public function __construct(private readonly NewsRepository $news)
    {
    }

    public function execute(int $retentionDays): int
    {
        $retentionDays = max(1, $retentionDays);

        return $this->news->deleteOlderThan($retentionDays);
    }
}
