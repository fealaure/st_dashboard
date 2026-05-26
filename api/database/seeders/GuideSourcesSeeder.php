<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class GuideSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'slug' => 'eurogamer-guides',
                'name' => 'Eurogamer Guides',
                'rss_url' => 'https://www.eurogamer.net/feed/guides',
                'website_url' => 'https://www.eurogamer.net/guides',
                'weight' => 1.00,
            ],
            [
                'slug' => 'kotaku-tips',
                'name' => 'Kotaku Tips',
                'rss_url' => 'https://kotaku.com/tag/tips/rss',
                'website_url' => 'https://kotaku.com/tag/tips',
                'weight' => 1.00,
            ],
            [
                'slug' => 'vgc-guides',
                'name' => 'VGC Guides',
                'rss_url' => 'https://www.videogameschronicle.com/category/guide/feed/',
                'website_url' => 'https://www.videogameschronicle.com/category/guide/',
                'weight' => 1.00,
            ],
        ];

        foreach ($sources as $source) {
            DB::table('guide_sources')->updateOrInsert(
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
