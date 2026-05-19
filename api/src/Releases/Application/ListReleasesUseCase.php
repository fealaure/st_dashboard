<?php

declare(strict_types=1);

namespace SaveState\Releases\Application;

use SaveState\Releases\Domain\Release;
use SaveState\Releases\Domain\ReleaseRepository;

final class ListReleasesUseCase
{
    public function __construct(private readonly ReleaseRepository $releases)
    {
    }

    /**
     * @return list<Release>
     */
    public function execute(int $limit = 100, int $daysAhead = 90): array
    {
        return $this->releases->upcoming(
            limit: max(1, min($limit, 500)),
            daysAhead: max(1, min($daysAhead, 365)),
        );
    }
}
