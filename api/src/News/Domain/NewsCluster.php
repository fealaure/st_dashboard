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
        public readonly float $thermometer,
        public readonly int $redditUpvotes,
        public readonly int $redditComments,
        public readonly ?DateTimeImmutable $redditSyncedAt,
        public readonly ?DateTimeImmutable $thermometerUpdatedAt,
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
            thermometer: 0.0,
            redditUpvotes: 0,
            redditComments: 0,
            redditSyncedAt: null,
            thermometerUpdatedAt: null,
            firstSeenAt: $publishedAt,
            lastSeenAt: $publishedAt,
        );
    }

    public function withRecomputedThermometer(
        float $newScore,
        DateTimeImmutable $now,
        DateTimeImmutable $latestPublishedAt,
    ): self {
        return new self(
            id: $this->id,
            simhash: $this->simhash,
            canonicalTitle: $this->canonicalTitle,
            canonicalUrl: $this->canonicalUrl,
            thermometer: $newScore,
            redditUpvotes: $this->redditUpvotes,
            redditComments: $this->redditComments,
            redditSyncedAt: $this->redditSyncedAt,
            thermometerUpdatedAt: $now,
            firstSeenAt: $this->firstSeenAt,
            lastSeenAt: $latestPublishedAt > $this->lastSeenAt ? $latestPublishedAt : $this->lastSeenAt,
        );
    }

    public function withRedditAggregate(int $upvotes, int $comments, DateTimeImmutable $syncedAt): self
    {
        return new self(
            id: $this->id,
            simhash: $this->simhash,
            canonicalTitle: $this->canonicalTitle,
            canonicalUrl: $this->canonicalUrl,
            thermometer: $this->thermometer,
            redditUpvotes: $upvotes,
            redditComments: $comments,
            redditSyncedAt: $syncedAt,
            thermometerUpdatedAt: $this->thermometerUpdatedAt,
            firstSeenAt: $this->firstSeenAt,
            lastSeenAt: $this->lastSeenAt,
        );
    }
}
