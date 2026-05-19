<?php

declare(strict_types=1);

namespace SaveState\Releases\Infrastructure;

use App\Models\ReleaseModel;
use DateTimeImmutable;
use SaveState\Releases\Domain\Release;
use SaveState\Releases\Domain\ReleaseRepository;

final class EloquentReleaseRepository implements ReleaseRepository
{
    public function upsert(Release $release): void
    {
        ReleaseModel::query()->updateOrCreate(
            ['igdb_id' => $release->igdbId],
            [
                'name' => $release->name,
                'slug' => $release->slug,
                'summary' => $release->summary,
                'cover_url' => $release->coverUrl,
                'hype' => $release->hype,
                'release_date' => $release->releaseDate?->format('Y-m-d'),
                'platforms' => $release->platforms,
                'publishers' => $release->publishers,
                'igdb_url' => $release->igdbUrl,
                'last_synced_at' => $release->lastSyncedAt,
            ],
        );
    }

    public function upcoming(int $limit, int $daysAhead): array
    {
        $today = now()->startOfDay();
        $until = $today->copy()->addDays(max(1, $daysAhead));

        $rows = ReleaseModel::query()
            ->whereNotNull('release_date')
            ->whereBetween('release_date', [$today, $until])
            ->orderBy('release_date')
            ->orderByDesc('hype')
            ->limit(max(1, min($limit, 500)))
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->toDomain($row);
        }

        return $result;
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        return (int) ReleaseModel::query()
            ->whereNotNull('release_date')
            ->where('release_date', '<', $cutoff->format('Y-m-d'))
            ->delete();
    }

    private function toDomain(ReleaseModel $row): Release
    {
        $releaseDate = null;
        if ($row->release_date !== null) {
            $releaseDate = DateTimeImmutable::createFromMutable($row->release_date->toDateTime());
        }

        return new Release(
            id: (int) $row->id,
            igdbId: (int) $row->igdb_id,
            name: (string) $row->name,
            slug: $row->slug,
            summary: $row->summary,
            coverUrl: $row->cover_url,
            hype: (int) $row->hype,
            releaseDate: $releaseDate,
            platforms: array_values((array) ($row->platforms ?? [])),
            publishers: array_values((array) ($row->publishers ?? [])),
            igdbUrl: $row->igdb_url,
            lastSyncedAt: DateTimeImmutable::createFromMutable($row->last_synced_at->toDateTime()),
        );
    }
}
