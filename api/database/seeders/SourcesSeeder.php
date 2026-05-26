<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'slug' => 'ign',
                'name' => 'IGN',
                'rss_url' => 'https://feeds.ign.com/ign/games-all',
                'website_url' => 'https://www.ign.com',
                'weight' => 1.00,
            ],
            [
                'slug' => 'gamespot',
                'name' => 'GameSpot',
                'rss_url' => 'https://www.gamespot.com/feeds/mashup/',
                'website_url' => 'https://www.gamespot.com',
                'weight' => 1.00,
            ],
            [
                'slug' => 'eurogamer',
                'name' => 'Eurogamer',
                'rss_url' => 'https://www.eurogamer.net/?format=rss',
                'website_url' => 'https://www.eurogamer.net',
                'weight' => 1.00,
            ],
            [
                'slug' => 'vgc',
                'name' => 'Video Games Chronicle',
                'rss_url' => 'https://www.videogameschronicle.com/feed/',
                'website_url' => 'https://www.videogameschronicle.com',
                'weight' => 1.00,
            ],
            [
                'slug' => 'kotaku',
                'name' => 'Kotaku',
                'rss_url' => 'https://kotaku.com/rss',
                'website_url' => 'https://kotaku.com',
                'weight' => 1.00,
            ],
        ];

        foreach ($sources as $source) {
            DB::table('sources')->updateOrInsert(
                ['slug' => $source['slug']],
                array_merge($source, [
                    'active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
