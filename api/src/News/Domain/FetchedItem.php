<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

final class FetchedItem
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
