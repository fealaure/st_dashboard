<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

final class NewsCluster
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $simhash,
        public readonly string $canonicalTitle,
        public readonly string $canonicalUrl,
        public readonly DateTimeImmutable $firstSeenAt,
        public readonly DateTimeImmutable $lastSeenAt,
    ) {
    }

    public static function fromFirstItem(
        int $simhash,
        string $title,
        string $url,
        DateTimeImmutable $publishedAt,
    ): self {
        return new self(
            id: null,
            simhash: $simhash,
            canonicalTitle: $title,
            canonicalUrl: $url,
            firstSeenAt: $publishedAt,
            lastSeenAt: $publishedAt,
        );
    }

    /**
     * Empurra a last_seen_at quando um item novo (mais recente) entra no cluster.
     */
    public function withLatestItem(DateTimeImmutable $latestPublishedAt): self
    {
        return new self(
            id: $this->id,
            simhash: $this->simhash,
            canonicalTitle: $this->canonicalTitle,
            canonicalUrl: $this->canonicalUrl,
            firstSeenAt: $this->firstSeenAt,
            lastSeenAt: $latestPublishedAt > $this->lastSeenAt ? $latestPublishedAt : $this->lastSeenAt,
        );
    }
}
