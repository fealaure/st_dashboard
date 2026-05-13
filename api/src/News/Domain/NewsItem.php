<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

final class NewsItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $sourceId,
        public readonly string $externalId,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $excerpt,
        public readonly ?string $author,
        public readonly DateTimeImmutable $publishedAt,
        public readonly DateTimeImmutable $fetchedAt,
    ) {
    }

    public static function newlyFetched(
        int $sourceId,
        string $externalId,
        string $title,
        string $url,
        ?string $excerpt,
        ?string $author,
        DateTimeImmutable $publishedAt,
        DateTimeImmutable $fetchedAt,
    ): self {
        return new self(
            id: null,
            sourceId: $sourceId,
            externalId: $externalId,
            title: $title,
            url: $url,
            excerpt: $excerpt,
            author: $author,
            publishedAt: $publishedAt,
            fetchedAt: $fetchedAt,
        );
    }
}
