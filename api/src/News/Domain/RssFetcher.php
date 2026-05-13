<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

interface RssFetcher
{
    /**
     * @return iterable<FetchedItem>
     */
    public function fetch(Source $source): iterable;
}
