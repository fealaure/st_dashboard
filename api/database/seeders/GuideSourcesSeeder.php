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
                'rss_url' => 'https://kotaku.com/game-tips/rss',
                'website_url' => 'https://kotaku.com/game-tips',
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

        // Lista declarativa: qualquer source que não esteja mais aqui é removido
        // (ex.: vgc-guides saiu). O cascadeOnDelete em guide_items.source_id limpa
        // os guias associados junto.
        $desiredSlugs = array_column($sources, 'slug');
        DB::table('guide_sources')->whereNotIn('slug', $desiredSlugs)->delete();
    }
}
