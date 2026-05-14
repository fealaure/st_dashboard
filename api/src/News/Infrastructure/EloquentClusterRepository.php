<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\NewsClusterModel;
use App\Models\NewsItemModel;
use DateTimeImmutable;
use Generator;
use Illuminate\Support\Facades\DB;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsCluster;

final class EloquentClusterRepository implements ClusterRepository
{
    public function recent(int $hoursWindow): Generator
    {
        $cutoff = now()->subHours(max(1, $hoursWindow));

        $rows = NewsClusterModel::query()
            ->where('last_seen_at', '>=', $cutoff)
            ->orderByDesc('last_seen_at')
            ->cursor();

        foreach ($rows as $row) {
            yield $this->toDomain($row);
        }
    }

    public function save(NewsCluster $cluster): NewsCluster
    {
        $row = NewsClusterModel::query()->create([
            'simhash' => $cluster->simhash,
            'canonical_title' => $cluster->canonicalTitle,
            'canonical_url' => $cluster->canonicalUrl,
            'thermometer' => $cluster->thermometer,
            'reddit_upvotes' => $cluster->redditUpvotes,
            'reddit_comments' => $cluster->redditComments,
            'reddit_synced_at' => $cluster->redditSyncedAt,
            'thermometer_updated_at' => $cluster->thermometerUpdatedAt,
            'first_seen_at' => $cluster->firstSeenAt,
            'last_seen_at' => $cluster->lastSeenAt,
        ]);

        return $this->toDomain($row);
    }

    public function update(NewsCluster $cluster): NewsCluster
    {
        if ($cluster->id === null) {
            throw new \InvalidArgumentException('Cannot update a cluster without id.');
        }

        $row = NewsClusterModel::query()->findOrFail($cluster->id);
        $row->fill([
            'thermometer' => $cluster->thermometer,
            'reddit_upvotes' => $cluster->redditUpvotes,
            'reddit_comments' => $cluster->redditComments,
            'reddit_synced_at' => $cluster->redditSyncedAt,
            'thermometer_updated_at' => $cluster->thermometerUpdatedAt,
            'last_seen_at' => $cluster->lastSeenAt,
        ]);
        $row->save();

        return $this->toDomain($row);
    }

    public function findById(int $id): ?NewsCluster
    {
        $row = NewsClusterModel::query()->find($id);

        return $row ? $this->toDomain($row) : null;
    }

    public function distinctSourcesCount(int $clusterId): int
    {
        return (int) NewsItemModel::query()
            ->where('cluster_id', $clusterId)
            ->distinct('source_id')
            ->count('source_id');
    }

    public function topByThermometer(int $limit, int $maxAgeHours): Generator
    {
        $cutoff = now()->subHours(max(1, $maxAgeHours));

        $clusters = NewsClusterModel::query()
            ->where('last_seen_at', '>=', $cutoff)
            ->orderByDesc('thermometer')
            ->orderByDesc('last_seen_at')
            ->limit($limit)
            ->get();

        foreach ($clusters as $clusterRow) {
            $sources = DB::table('news_items')
                ->join('sources', 'sources.id', '=', 'news_items.source_id')
                ->where('news_items.cluster_id', $clusterRow->id)
                ->distinct()
                ->select('sources.slug', 'sources.name')
                ->orderBy('sources.name')
                ->get()
                ->map(fn ($row) => ['slug' => (string) $row->slug, 'name' => (string) $row->name])
                ->toArray();

            $latest = $this->getLatestPublishedAt((int) $clusterRow->id)
                ?? DateTimeImmutable::createFromMutable($clusterRow->last_seen_at->toDateTime());

            yield [$this->toDomain($clusterRow), $sources, $latest];
        }
    }

    public function dueForRedditSync(int $maxAgeHours, int $resyncAfterHours): Generator
    {
        $cutoff = now()->subHours(max(1, $maxAgeHours));
        $resyncBefore = now()->subHours(max(1, $resyncAfterHours));

        $rows = NewsClusterModel::query()
            ->where('last_seen_at', '>=', $cutoff)
            ->where(function ($q) use ($resyncBefore): void {
                $q->whereNull('reddit_synced_at')
                  ->orWhere('reddit_synced_at', '<', $resyncBefore);
            })
            ->orderBy('reddit_synced_at')
            ->cursor();

        foreach ($rows as $row) {
            yield $this->toDomain($row);
        }
    }

    public function recentForSnapshot(int $maxAgeHours): Generator
    {
        $cutoff = now()->subHours(max(1, $maxAgeHours));

        $rows = NewsClusterModel::query()
            ->where('last_seen_at', '>=', $cutoff)
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $row) {
            yield $this->toDomain($row);
        }
    }

    public function getLatestPublishedAt(int $clusterId): ?DateTimeImmutable
    {
        $row = NewsItemModel::query()
            ->where('cluster_id', $clusterId)
            ->orderByDesc('published_at')
            ->first();

        return $row ? DateTimeImmutable::createFromMutable($row->published_at->toDateTime()) : null;
    }

    private function toDomain(NewsClusterModel $row): NewsCluster
    {
        return new NewsCluster(
            id: (int) $row->id,
            simhash: (int) $row->simhash,
            canonicalTitle: (string) $row->canonical_title,
            canonicalUrl: (string) $row->canonical_url,
            thermometer: (float) $row->thermometer,
            redditUpvotes: (int) $row->reddit_upvotes,
            redditComments: (int) $row->reddit_comments,
            redditSyncedAt: $row->reddit_synced_at
                ? DateTimeImmutable::createFromMutable($row->reddit_synced_at->toDateTime())
                : null,
            thermometerUpdatedAt: $row->thermometer_updated_at
                ? DateTimeImmutable::createFromMutable($row->thermometer_updated_at->toDateTime())
                : null,
            firstSeenAt: DateTimeImmutable::createFromMutable($row->first_seen_at->toDateTime()),
            lastSeenAt: DateTimeImmutable::createFromMutable($row->last_seen_at->toDateTime()),
        );
    }
}
