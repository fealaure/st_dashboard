<?php

declare(strict_types=1);

namespace SaveState\News\Application;

use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsCluster;
use SaveState\News\Domain\NewsItem;
use SaveState\News\Domain\NewsRepository;
use SaveState\News\Domain\SimhashHasher;

final class ClusterizeNewsItemUseCase
{
    /**
     * Janela de tempo (em horas) em que ainda procuramos clusters existentes
     * para anexar um novo item. Notícias mais velhas que isso não viram parte
     * do mesmo cluster de algo publicado hoje.
     */
    private const MATCH_WINDOW_HOURS = 72;

    /**
     * Hamming distance máxima (em bits) entre dois simhashes pra serem
     * considerados o mesmo assunto. Para simhash de 32 bits, 3 bits
     * costuma ser um bom corte.
     */
    private const MATCH_THRESHOLD_BITS = 3;

    public function __construct(
        private readonly NewsRepository $news,
        private readonly ClusterRepository $clusters,
        private readonly SimhashHasher $hasher,
    ) {
    }

    public function execute(NewsItem $item): NewsCluster
    {
        if ($item->id === null) {
            throw new \InvalidArgumentException('Item precisa estar persistido (id != null) antes de clusterizar.');
        }

        $simhash = $this->hasher->hash($item->title);
        $match = $this->findMatch($simhash);

        if ($match === null) {
            $cluster = $this->clusters->save(
                NewsCluster::fromFirstItem(
                    simhash: $simhash,
                    title: $item->title,
                    url: $item->url,
                    publishedAt: $item->publishedAt,
                )
            );
        } else {
            $cluster = $match;
        }

        if ($cluster->id === null) {
            throw new \RuntimeException('Cluster persistido sem id — repo violou contrato.');
        }

        $this->news->assignToCluster($item->id, $cluster->id);

        return $this->clusters->update($cluster->withLatestItem($item->publishedAt));
    }

    private function findMatch(int $simhash): ?NewsCluster
    {
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($this->clusters->recent(self::MATCH_WINDOW_HOURS) as $candidate) {
            $distance = $this->hasher->distance($simhash, $candidate->simhash);

            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $bestDistance <= self::MATCH_THRESHOLD_BITS ? $best : null;
    }
}
