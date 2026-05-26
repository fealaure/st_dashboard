<?php

declare(strict_types=1);

namespace SaveState\Guides\Domain;

interface RssFetcher
{
    /** @return iterable<FetchedGuide> */
    public function fetch(GuideSource $source): iterable;
}
