<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface ClusterRepository
{
    /**
     * Procura clusters cuja last_seen_at esteja dentro da janela de horas
     * informada. Usado pra restringir o matching de simhash a clusters
     * recentes (não faz sentido juntar notícia de hoje com cluster de 3
     * semanas atrás).
     *
     * @return iterable<NewsCluster>
     */
    public function recent(int $hoursWindow): iterable;

    public function save(NewsCluster $cluster): NewsCluster;

    public function update(NewsCluster $cluster): NewsCluster;

    public function findById(int $id): ?NewsCluster;

    /**
     * Quantas fontes distintas têm pelo menos um news_item ligado a esse cluster.
     */
    public function distinctSourcesCount(int $clusterId): int;

    /**
     * @return iterable<array{NewsCluster, list<array{slug:string,name:string}>, \DateTimeImmutable}>
     *   cluster, fontes distintas que cobriram, latestPublishedAt
     */
    public function topByThermometer(int $limit, int $maxAgeHours): iterable;

    /**
     * Clusters cuja last_seen_at está dentro da janela e que ainda não foram
     * sincronizados com o Reddit, ou cujo último sync foi há mais de N horas.
     *
     * @return iterable<NewsCluster>
     */
    public function dueForRedditSync(int $maxAgeHours, int $resyncAfterHours): iterable;

    /**
     * @return iterable<NewsCluster>
     */
    public function recentForSnapshot(int $maxAgeHours): iterable;

    public function getLatestPublishedAt(int $clusterId): ?\DateTimeImmutable;
}
