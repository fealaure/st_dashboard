<?php

declare(strict_types=1);

namespace SaveState\Releases\Domain;

use DateTimeImmutable;

final class Release
{
    /**
     * @param list<string> $platforms
     * @param list<string> $publishers
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $igdbId,
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $summary,
        public readonly ?string $coverUrl,
        public readonly int $hype,
        public readonly ?DateTimeImmutable $releaseDate,
        public readonly array $platforms,
        public readonly array $publishers,
        public readonly ?string $igdbUrl,
        public readonly DateTimeImmutable $lastSyncedAt,
    ) {
    }
}
