<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\RedditSignalModel;
use SaveState\News\Domain\RedditSignal;
use SaveState\News\Domain\RedditSignalRepository;

final class EloquentRedditSignalRepository implements RedditSignalRepository
{
    public function upsert(RedditSignal $signal): void
    {
        RedditSignalModel::query()->updateOrCreate(
            [
                'cluster_id' => $signal->clusterId,
                'reddit_post_id' => $signal->redditPostId,
            ],
            [
                'subreddit' => $signal->subreddit,
                'title' => $signal->title,
                'permalink' => $signal->permalink,
                'score' => $signal->score,
                'num_comments' => $signal->numComments,
                'posted_at' => $signal->postedAt,
                'captured_at' => $signal->capturedAt,
            ],
        );
    }

    public function aggregateForCluster(int $clusterId): array
    {
        $row = RedditSignalModel::query()
            ->where('cluster_id', $clusterId)
            ->selectRaw('COALESCE(SUM(score), 0) AS upvotes, COALESCE(SUM(num_comments), 0) AS comments')
            ->first();

        return [
            'upvotes' => (int) ($row?->upvotes ?? 0),
            'comments' => (int) ($row?->comments ?? 0),
        ];
    }
}
