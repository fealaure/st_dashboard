<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove o termômetro de relevância e toda a integração com o Reddit.
 *
 * O feed de notícias passa a ser cronológico (ordenado por last_seen_at),
 * apoiado só no clustering por simhash. Tabelas e colunas dedicadas a
 * score/upvotes deixam de existir.
 *
 * Idempotente de propósito: produção às vezes fica em estado meio-aplicado.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('reddit_signals');
        Schema::dropIfExists('thermometer_snapshots');

        Schema::table('news_clusters', function (Blueprint $table): void {
            if (Schema::hasColumn('news_clusters', 'thermometer')) {
                // O índice foi criado junto com a coluna (->index()); remova antes.
                $table->dropIndex('news_clusters_thermometer_index');
                $table->dropColumn('thermometer');
            }
            foreach (['thermometer_updated_at', 'reddit_upvotes', 'reddit_comments', 'reddit_synced_at'] as $column) {
                if (Schema::hasColumn('news_clusters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_clusters', function (Blueprint $table): void {
            if (! Schema::hasColumn('news_clusters', 'thermometer')) {
                $table->decimal('thermometer', 5, 2)->default(0.00)->index()->after('canonical_url');
            }
            if (! Schema::hasColumn('news_clusters', 'reddit_upvotes')) {
                $table->integer('reddit_upvotes')->default(0)->after('thermometer');
            }
            if (! Schema::hasColumn('news_clusters', 'reddit_comments')) {
                $table->integer('reddit_comments')->default(0)->after('reddit_upvotes');
            }
            if (! Schema::hasColumn('news_clusters', 'reddit_synced_at')) {
                $table->timestamp('reddit_synced_at')->nullable()->after('reddit_comments');
            }
            if (! Schema::hasColumn('news_clusters', 'thermometer_updated_at')) {
                $table->timestamp('thermometer_updated_at')->nullable()->after('reddit_synced_at');
            }
        });

        if (! Schema::hasTable('reddit_signals')) {
            Schema::create('reddit_signals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cluster_id')->constrained('news_clusters')->cascadeOnDelete();
                $table->string('reddit_post_id');
                $table->string('subreddit');
                $table->string('title', 500);
                $table->text('permalink');
                $table->integer('score')->default(0);
                $table->integer('num_comments')->default(0);
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('captured_at')->useCurrent();
                $table->unique(['cluster_id', 'reddit_post_id']);
                $table->index(['cluster_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('thermometer_snapshots')) {
            Schema::create('thermometer_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cluster_id')->constrained('news_clusters')->cascadeOnDelete();
                $table->decimal('thermometer', 5, 2)->default(0.00);
                $table->decimal('coverage_component', 5, 2)->default(0.00);
                $table->decimal('reddit_component', 5, 2)->default(0.00);
                $table->decimal('recency_component', 5, 2)->default(0.00);
                $table->timestamp('captured_at')->useCurrent();
                $table->index(['cluster_id', 'captured_at']);
            });
        }
    }
};
