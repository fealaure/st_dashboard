<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use Psr\Log\LoggerInterface;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\RedditClient;
use SaveState\News\Domain\RedditSignal;
use SaveState\News\Domain\RedditSignalRepository;
use SaveState\Shared\Domain\Clock;
use Throwable;

final class FetchRedditSignalsUseCase
{
    /** Janela máxima (em horas) pra buscar Reddit pros clusters recentes. */
    private const MAX_AGE_HOURS = 72;

    /** Re-sincroniza se o último sync foi há mais de N horas. */
    private const RESYNC_AFTER_HOURS = 2;

    public function __construct(
        private readonly ClusterRepository $clusters,
        private readonly RedditClient $reddit,
        private readonly RedditSignalRepository $signals,
        private readonly RecomputeThermometerUseCase $recompute,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(int $maxClusters = 40): FetchRedditSignalsResult
    {
        $processed = 0;
        $signalsCaptured = 0;
        $errors = [];

        foreach ($this->clusters->dueForRedditSync(self::MAX_AGE_HOURS, self::RESYNC_AFTER_HOURS) as $cluster) {
            if ($processed >= $maxClusters) {
                break;
            }

            try {
                $count = $this->syncCluster($cluster);
                $signalsCaptured += $count;
                $processed++;
            } catch (Throwable $e) {
                $this->logger->error('Reddit sync failed for cluster', [
                    'cluster_id' => $cluster->id,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = sprintf('cluster %d: %s', (int) $cluster->id, $e->getMessage());
            }
        }

        return new FetchRedditSignalsResult(
            clustersProcessed: $processed,
            signalsCaptured: $signalsCaptured,
            errors: $errors,
        );
    }

    private function syncCluster(\SaveState\News\Domain\NewsCluster $cluster): int
    {
        $now = $this->clock->now();
        $posts = $this->reddit->searchByUrl($cluster->canonicalUrl);

        $totalUpvotes = 0;
        $totalComments = 0;

        foreach ($posts as $post) {
            if ($post->id === '') {
                continue;
            }

            $this->signals->upsert(new RedditSignal(
                id: null,
                clusterId: (int) $cluster->id,
                redditPostId: $post->id,
                subreddit: $post->subreddit,
                title: $post->title,
                permalink: $post->permalink,
                score: $post->score,
                numComments: $post->numComments,
                postedAt: $post->postedAt,
                capturedAt: $now,
            ));

            $totalUpvotes += max(0, $post->score);
            $totalComments += max(0, $post->numComments);
        }

        $updated = $cluster->withRedditAggregate($totalUpvotes, $totalComments, $now);
        $this->clusters->update($updated);

        $this->recompute->execute($updated);

        return count($posts);
    }
}
