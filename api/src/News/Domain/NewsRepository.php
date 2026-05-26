<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface NewsRepository
{
    public function existsByExternalId(int $sourceId, string $externalId): bool;

    /**
     * Verifica se já existe um item com a URL informada em qualquer source.
     * Usado pra evitar duplicação quando um mesmo artigo aparece em mais de
     * um feed do mesmo publisher (ex.: feed geral + feed de uma tag).
     */
    public function existsByUrl(string $url): bool;

    public function save(NewsItem $item): NewsItem;

    /** @return iterable<array{NewsItem, Source}> */
    public function latest(int $limit): iterable;

    public function deleteOlderThan(int $days): int;

    public function assignToCluster(int $itemId, int $clusterId): void;

    /** @return iterable<NewsItem> */
    public function withoutCluster(): iterable;
}
