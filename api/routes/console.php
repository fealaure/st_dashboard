<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:ingest')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('guides:ingest')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('news:prune', ['--days=30'])
    ->dailyAt('03:00');

Schedule::command('releases:sync')
    ->dailyAt('04:00')
    ->withoutOverlapping();
