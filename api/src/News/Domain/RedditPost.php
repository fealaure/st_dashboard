<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

/**
 * Representação leve de um post do Reddit retornado pela API.
 * Quando persistido vinculado a um cluster, vira RedditSignal.
 */
final class RedditPost
{
    public function __construct(
        public readonly string $id,
        public readonly string $subreddit,
        public readonly string $title,
        public readonly string $permalink,
        public readonly int $score,
        public readonly int $numComments,
        public readonly DateTimeImmutable $postedAt,
    ) {
    }
}
