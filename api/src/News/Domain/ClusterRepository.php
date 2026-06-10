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
     * Clusters recentes (last_seen_at dentro da janela), ordenados do mais
     * recente pro mais antigo, já com as fontes distintas que cobriram e a
     * data de publicação mais recente.
     *
     * @return iterable<array{NewsCluster, list<array{slug:string,name:string}>, \DateTimeImmutable}>
     *   cluster, fontes distintas que cobriram, latestPublishedAt
     */
    public function recentWithSources(int $limit, int $maxAgeHours): iterable;

    public function getLatestPublishedAt(int $clusterId): ?\DateTimeImmutable;
}
