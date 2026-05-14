<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface RedditClient
{
    /**
     * Procura posts no Reddit que linkam para a URL fornecida.
     *
     * @return list<RedditPost>
     */
    public function searchByUrl(string $url): array;
}
