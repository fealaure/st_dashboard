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

    public function recentWithSources(int $limit, int $maxAgeHours): Generator
    {
        $cutoff = now()->subHours(max(1, $maxAgeHours));

        $clusters = NewsClusterModel::query()
            ->where('last_seen_at', '>=', $cutoff)
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
            firstSeenAt: DateTimeImmutable::createFromMutable($row->first_seen_at->toDateTime()),
            lastSeenAt: DateTimeImmutable::createFromMutable($row->last_seen_at->toDateTime()),
        );
    }
}
