<?php

declare(strict_types=1);

namespace SaveState\News\Infrastructure;

use App\Models\NewsItemModel;
use App\Models\SourceModel;
use DateTimeImmutable;
use Generator;
use SaveState\News\Domain\NewsItem;
use SaveState\News\Domain\NewsRepository;
use SaveState\News\Domain\Source;

final class EloquentNewsRepository implements NewsRepository
{
    public function existsByExternalId(int $sourceId, string $externalId): bool
    {
        return NewsItemModel::query()
            ->where('source_id', $sourceId)
            ->where('external_id', $externalId)
            ->exists();
    }

    public function existsByUrl(string $url): bool
    {
        return NewsItemModel::query()->where('url', $url)->exists();
    }

    public function save(NewsItem $item): NewsItem
    {
        $row = NewsItemModel::query()->create([
            'source_id' => $item->sourceId,
            'external_id' => $item->externalId,
            'title' => $item->title,
            'url' => $item->url,
            'excerpt' => $item->excerpt,
            'author' => $item->author,
            'published_at' => $item->publishedAt,
            'fetched_at' => $item->fetchedAt,
        ]);

        return new NewsItem(
            id: (int) $row->id,
            sourceId: $item->sourceId,
            externalId: $item->externalId,
            title: $item->title,
            url: $item->url,
            excerpt: $item->excerpt,
            author: $item->author,
            publishedAt: $item->publishedAt,
            fetchedAt: $item->fetchedAt,
        );
    }

    public function deleteOlderThan(int $days): int
    {
        return (int) NewsItemModel::query()
            ->where('published_at', '<', now()->subDays($days))
            ->delete();
    }

    public function assignToCluster(int $itemId, int $clusterId): void
    {
        NewsItemModel::query()->whereKey($itemId)->update(['cluster_id' => $clusterId]);
    }

    public function withoutCluster(): Generator
    {
        $rows = NewsItemModel::query()
            ->whereNull('cluster_id')
            ->orderBy('published_at')
            ->cursor();

        foreach ($rows as $row) {
            yield $this->toNewsItem($row);
        }
    }

    public function latest(int $limit): Generator
    {
        $rows = NewsItemModel::query()
            ->with('source')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            yield [$this->toNewsItem($row), $this->toSource($row->source)];
        }
    }

    private function toNewsItem(NewsItemModel $row): NewsItem
    {
        return new NewsItem(
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

    private function toSource(SourceModel $row): Source
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
