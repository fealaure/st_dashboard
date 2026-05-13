<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\NewsClusterModel;
use App\Models\NewsItemModel;
use App\Models\SourceModel;
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

            $latestRow = NewsItemModel::query()
                ->where('cluster_id', $clusterRow->id)
                ->orderByDesc('published_at')
                ->first();

            $latestPublishedAt = $latestRow
                ? DateTimeImmutable::createFromMutable($latestRow->published_at->toDateTime())
                : DateTimeImmutable::createFromMutable($clusterRow->last_seen_at->toDateTime());

            yield [$this->toDomain($clusterRow), $sources, $latestPublishedAt];
        }
    }

    private function toDomain(NewsClusterModel $row): NewsCluster
    {
        return new NewsCluster(
            id: (int) $row->id,
            simhash: (int) $row->simhash,
            canonicalTitle: (string) $row->canonical_title,
            canonicalUrl: (string) $row->canonical_url,
            thermometer: (float) $row->thermometer,
            thermometerUpdatedAt: $row->thermometer_updated_at
                ? DateTimeImmutable::createFromMutable($row->thermometer_updated_at->toDateTime())
                : null,
            firstSeenAt: DateTimeImmutable::createFromMutable($row->first_seen_at->toDateTime()),
            lastSeenAt: DateTimeImmutable::createFromMutable($row->last_seen_at->toDateTime()),
        );
    }
}
