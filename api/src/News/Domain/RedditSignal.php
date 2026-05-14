<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

final class RedditSignal
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $clusterId,
        public readonly string $redditPostId,
        public readonly string $subreddit,
        public readonly string $title,
        public readonly string $permalink,
        public readonly int $score,
        public readonly int $numComments,
        public readonly DateTimeImmutable $postedAt,
        public readonly DateTimeImmutable $capturedAt,
    ) {
    }
}
