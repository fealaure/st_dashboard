<?php

declare(strict_types=1);

namespace SaveState\Releases\Domain;

interface IgdbClient
{
    /**
     * Busca lançamentos cuja data esteja entre $from e $until, restritos
     * aos IDs de plataforma fornecidos.
     *
     * @param list<int> $platformIds
     * @return list<Release>
     */
    public function fetchUpcoming(
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
        array $platformIds,
        int $limit,
    ): array;
}
