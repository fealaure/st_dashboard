<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

final class Source
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $rssUrl,
        public readonly ?string $websiteUrl,
        public readonly float $weight,
        public readonly bool $active,
    ) {
    }
}
