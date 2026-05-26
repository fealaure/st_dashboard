<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use SaveState\Guides\Domain\GuideRepository;
use SaveState\Guides\Domain\GuideSourceRepository;
use SaveState\Guides\Domain\RssFetcher as GuideRssFetcher;
use SaveState\Guides\Infrastructure\EloquentGuideRepository;
use SaveState\Guides\Infrastructure\EloquentGuideSourceRepository;
use SaveState\Guides\Infrastructure\SimplePieRssFetcher as GuideSimplePieRssFetcher;
use SaveState\News\Domain\ClusterRepository;
use SaveState\News\Domain\NewsRepository;
use SaveState\News\Domain\RedditClient;
use SaveState\News\Domain\RedditSignalRepository;
use SaveState\News\Domain\RssFetcher;
use SaveState\News\Domain\SimhashHasher;
use SaveState\News\Domain\SourceRepository;
use SaveState\News\Domain\ThermometerSnapshotRepository;
use SaveState\News\Infrastructure\EloquentClusterRepository;
use SaveState\News\Infrastructure\EloquentNewsRepository;
use SaveState\News\Infrastructure\EloquentRedditSignalRepository;
use SaveState\News\Infrastructure\EloquentSourceRepository;
use SaveState\News\Infrastructure\EloquentThermometerSnapshotRepository;
use SaveState\News\Infrastructure\PhpSimhashHasher;
use SaveState\News\Infrastructure\RedditApiClient;
use SaveState\News\Infrastructure\SimplePieRssFetcher;
use SaveState\Releases\Domain\IgdbClient;
use SaveState\Releases\Domain\ReleaseRepository;
use SaveState\Releases\Infrastructure\EloquentReleaseRepository;
use SaveState\Releases\Infrastructure\IgdbApiClient;
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
        $this->app->bind(RedditSignalRepository::class, EloquentRedditSignalRepository::class);
        $this->app->bind(ThermometerSnapshotRepository::class, EloquentThermometerSnapshotRepository::class);
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

        $this->app->bind(GuideSourceRepository::class, EloquentGuideSourceRepository::class);
        $this->app->bind(GuideRepository::class, EloquentGuideRepository::class);

        $this->app->bind(GuideRssFetcher::class, function (Application $app): GuideSimplePieRssFetcher {
            $cacheDir = storage_path('app/rss-cache');
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true);
            }

            return new GuideSimplePieRssFetcher(
                logger: $app->make(LoggerInterface::class),
                cacheDir: $cacheDir,
                cacheDurationSeconds: 600,
            );
        });

        $this->app->singleton(RedditClient::class, function (Application $app): RedditApiClient {
            $config = $app['config']->get('services.reddit', []);

            return new RedditApiClient(
                http: $app->make(HttpFactory::class),
                cache: $app->make(CacheRepository::class),
                logger: $app->make(LoggerInterface::class),
                clientId: (string) ($config['client_id'] ?? ''),
                clientSecret: (string) ($config['client_secret'] ?? ''),
                username: (string) ($config['username'] ?? ''),
                password: (string) ($config['password'] ?? ''),
                userAgent: (string) ($config['user_agent'] ?? 'SaveStateDashboard/0.1'),
            );
        });

        $this->app->bind(ReleaseRepository::class, EloquentReleaseRepository::class);

        $this->app->singleton(IgdbClient::class, function (Application $app): IgdbApiClient {
            $config = $app['config']->get('services.twitch', []);

            return new IgdbApiClient(
                http: $app->make(HttpFactory::class),
                cache: $app->make(CacheRepository::class),
                logger: $app->make(LoggerInterface::class),
                clock: $app->make(Clock::class),
                clientId: (string) ($config['client_id'] ?? ''),
                clientSecret: (string) ($config['client_secret'] ?? ''),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
