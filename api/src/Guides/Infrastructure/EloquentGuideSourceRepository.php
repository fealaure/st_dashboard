<?php

declare(strict_types=1);

namespace SaveState\Guides\Infrastructure;

use App\Models\GuideSourceModel;
use Generator;
use SaveState\Guides\Domain\GuideSource;
use SaveState\Guides\Domain\GuideSourceRepository;
use SaveState\Shared\Domain\Clock;

final class EloquentGuideSourceRepository implements GuideSourceRepository
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function allActive(): Generator
    {
        foreach (GuideSourceModel::query()->where('active', true)->orderBy('id')->cursor() as $row) {
            yield $this->toDomain($row);
        }
    }

    public function markFetched(int $sourceId): void
    {
        GuideSourceModel::query()
            ->whereKey($sourceId)
            ->update(['last_fetched_at' => $this->clock->now()]);
    }

    private function toDomain(GuideSourceModel $row): GuideSource
    {
        return new GuideSource(
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
