<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface SimhashHasher
{
    /**
     * Retorna simhash de 32 bits do texto fornecido.
     */
    public function hash(string $text): int;

    /**
     * Hamming distance entre dois simhashes de mesmo tamanho.
     */
    public function distance(int $a, int $b): int;
}
