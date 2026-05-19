<?php

declare(strict_types=1);

namespace SaveState\Releases\Application;

use Psr\Log\LoggerInterface;
use SaveState\Releases\Domain\IgdbClient;
use SaveState\Releases\Domain\ReleaseRepository;
use SaveState\Shared\Domain\Clock;

final class SyncReleasesUseCase
{
    /** Plataformas IGDB consideradas "principais" pra cobertura editorial. */
    public const PLATFORM_PC = 6;
    public const PLATFORM_SWITCH = 130;
    public const PLATFORM_PS5 = 167;
    public const PLATFORM_XBOX_SERIES = 169;
    public const PLATFORM_SWITCH_2 = 508;

    public const MAIN_PLATFORMS = [
        self::PLATFORM_PC,
        self::PLATFORM_SWITCH,
        self::PLATFORM_PS5,
        self::PLATFORM_XBOX_SERIES,
        self::PLATFORM_SWITCH_2,
    ];

    public function __construct(
        private readonly IgdbClient $igdb,
        private readonly ReleaseRepository $releases,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(int $daysAhead = 90, int $limit = 500): SyncReleasesResult
    {
        $from = $this->clock->now()->setTime(0, 0);
        $until = $from->modify("+{$daysAhead} days")->setTime(23, 59, 59);

        $fetched = $this->igdb->fetchUpcoming($from, $until, self::MAIN_PLATFORMS, $limit);

        $upserted = 0;
        foreach ($fetched as $release) {
            $this->releases->upsert($release);
            $upserted++;
        }

        $this->logger->info('Releases sync done', [
            'fetched' => count($fetched),
            'upserted' => $upserted,
        ]);

        return new SyncReleasesResult(
            fetched: count($fetched),
            upserted: $upserted,
        );
    }
}
