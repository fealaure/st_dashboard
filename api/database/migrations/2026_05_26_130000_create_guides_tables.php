<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guide_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('rss_url', 500);
            $table->string('website_url', 500)->nullable();
            $table->decimal('weight', 4, 2)->default(1.00);
            $table->boolean('active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guide_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('guide_sources')->cascadeOnDelete();
            $table->string('external_id', 128);
            $table->string('title', 500);
            $table->text('url');
            $table->text('excerpt')->nullable();
            $table->string('author', 200)->nullable();
            $table->timestamp('published_at')->useCurrent()->index();
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamps();

            $table->unique(['source_id', 'external_id']);
        });

        DB::transaction(function (): void {
            // Copia sources do tipo guide para guide_sources, preservando ids.
            DB::statement(<<<'SQL'
                INSERT INTO guide_sources (id, slug, name, rss_url, website_url, weight, active, last_fetched_at, created_at, updated_at)
                SELECT id, slug, name, rss_url, website_url, weight, active, last_fetched_at, created_at, updated_at
                FROM sources
                WHERE kind = 'guide'
            SQL);

            // Copia news_items do tipo guide para guide_items, preservando ids (e o vínculo com guide_sources).
            DB::statement(<<<'SQL'
                INSERT INTO guide_items (id, source_id, external_id, title, url, excerpt, author, published_at, fetched_at, created_at, updated_at)
                SELECT id, source_id, external_id, title, url, excerpt, author, published_at, fetched_at, created_at, updated_at
                FROM news_items
                WHERE kind = 'guide'
            SQL);

            // Remove os itens migrados de news_items.
            DB::table('news_items')->where('kind', 'guide')->delete();

            // Remove os clusters de guia (snapshots/reddit_signals caem via cascadeOnDelete das migrations anteriores).
            DB::table('news_clusters')->where('kind', 'guide')->delete();

            // Remove os sources de guia do `sources`.
            DB::table('sources')->where('kind', 'guide')->delete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_items');
        Schema::dropIfExists('guide_sources');
    }
};
