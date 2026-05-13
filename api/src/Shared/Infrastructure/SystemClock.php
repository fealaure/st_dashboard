<?php

declare(strict_types=1);

namespace SaveState\Shared\Infrastructure;

use DateTimeImmutable;
use SaveState\Shared\Domain\Clock;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
