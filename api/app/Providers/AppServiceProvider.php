<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsRepository;
use SaveState\News\Domain\RssFetcher;
use SaveState\News\Domain\SimhashHasher;
use SaveState\News\Domain\SourceRepository;
use SaveState\News\Infrastructure\EloquentClusterRepository;
use SaveState\News\Infrastructure\EloquentNewsRepository;
use SaveState\News\Infrastructure\EloquentSourceRepository;
use SaveState\News\Infrastructure\PhpSimhashHasher;
use SaveState\News\Infrastructure\SimplePieRssFetcher;
use SaveState\Shared\Domain\Clock;
use SaveState\Shared\Infrastructure\SystemClock;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);

        $this->app->bind(SourceRepository::class, EloquentSourceRepository::class);
        $this->app->bind(NewsRepository::class, EloquentNewsRepository::class);
        $this->app->bind(ClusterRepository::class, EloquentClusterRepository::class);
        $this->app->singleton(SimhashHasher::class, PhpSimhashHasher::class);

        $this->app->bind(RssFetcher::class, function (Application $app): SimplePieRssFetcher {
            $cacheDir = storage_path('app/rss-cache');
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            return new SimplePieRssFetcher(
                logger: $app->make(LoggerInterface::class),
                cacheDir: $cacheDir,
                cacheDurationSeconds: 600,
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
