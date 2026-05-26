<?php

declare(strict_types=1);

namespace SaveState\Guides\Infrastructure;

use App\Models\GuideItemModel;
use App\Models\GuideSourceModel;
use DateTimeImmutable;
use Generator;
use SaveState\Guides\Domain\Guide;
use SaveState\Guides\Domain\GuideRepository;
use SaveState\Guides\Domain\GuideSource;

final class EloquentGuideRepository implements GuideRepository
{
    public function existsByExternalId(int $sourceId, string $externalId): bool
    {
        return GuideItemModel::query()
            ->where('source_id', $sourceId)
            ->where('external_id', $externalId)
            ->exists();
    }

    public function existsByUrl(string $url): bool
    {
        return GuideItemModel::query()->where('url', $url)->exists();
    }

    public function save(Guide $guide): Guide
    {
        $row = GuideItemModel::query()->create([
            'source_id' => $guide->sourceId,
            'external_id' => $guide->externalId,
            'title' => $guide->title,
            'url' => $guide->url,
            'excerpt' => $guide->excerpt,
            'author' => $guide->author,
            'published_at' => $guide->publishedAt,
            'fetched_at' => $guide->fetchedAt,
        ]);

        return new Guide(
            id: (int) $row->id,
            sourceId: $guide->sourceId,
            externalId: $guide->externalId,
            title: $guide->title,
            url: $guide->url,
            excerpt: $guide->excerpt,
            author: $guide->author,
            publishedAt: $guide->publishedAt,
            fetchedAt: $guide->fetchedAt,
        );
    }

    public function deleteOlderThan(int $days): int
    {
        return (int) GuideItemModel::query()
            ->where('published_at', '<', now()->subDays($days))
            ->delete();
    }

    public function listRecent(int $limit, int $maxAgeHours): Generator
    {
        $cutoff = now()->subHours(max(1, $maxAgeHours));

        $rows = GuideItemModel::query()
            ->with('source')
            ->where('published_at', '>=', $cutoff)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            yield [$this->toGuide($row), $this->toSource($row->source)];
        }
    }

    private function toGuide(GuideItemModel $row): Guide
    {
        return new Guide(
            id: (int) $row->id,
            sourceId: (int) $row->source_id,
            externalId: (string) $row->external_id,
            title: (string) $row->title,
            url: (string) $row->url,
            excerpt: $row->excerpt,
            author: $row->author,
            publishedAt: DateTimeImmutable::createFromMutable($row->published_at->toDateTime()),
            fetchedAt: DateTimeImmutable::createFromMutable($row->fetched_at->toDateTime()),
        );
    }

    private function toSource(GuideSourceModel $row): GuideSource
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
