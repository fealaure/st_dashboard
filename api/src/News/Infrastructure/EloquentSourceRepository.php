<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\SourceModel;
use Generator;
use SaveState\News\Domain\Source;
use SaveState\News\Domain\SourceRepository;
use SaveState\Shared\Domain\Clock;

final class EloquentSourceRepository implements SourceRepository
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function allActive(): Generator
    {
        foreach (SourceModel::query()->where('active', true)->orderBy('id')->cursor() as $row) {
            yield $this->toDomain($row);
        }
    }

    public function markFetched(int $sourceId): void
    {
        SourceModel::query()
            ->whereKey($sourceId)
            ->update(['last_fetched_at' => $this->clock->now()]);
    }

    public function countActive(): int
    {
        return (int) SourceModel::query()->where('active', true)->count();
    }

    private function toDomain(SourceModel $row): Source
    {
        return new Source(
            id: (int) $row->id,
            slug: (string) $row->slug,
            name: (string) $row->name,
            rssUrl: (string) $row->rss_url,
            websiteUrl: $row->website_url,
            weight: (float) $row->weight,
            active: (bool) $row->active,
        );
    }
}
