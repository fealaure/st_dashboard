<?php

declare(strict_types=1);

namespace SaveState\Guides\Domain;

use DateTimeImmutable;

final class FetchedGuide
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $excerpt,
        public readonly ?string $author,
        public readonly DateTimeImmutable $publishedAt,
    ) {
    }
}
