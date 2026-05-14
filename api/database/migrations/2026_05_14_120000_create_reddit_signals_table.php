<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reddit_signals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cluster_id')->constrained('news_clusters')->cascadeOnDelete();
            $table->string('reddit_post_id', 32);
            $table->string('subreddit', 100);
            $table->string('title', 500);
            $table->string('permalink', 500);
            $table->integer('score')->default(0);
            $table->integer('num_comments')->default(0);
            $table->timestamp('posted_at');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(['cluster_id', 'reddit_post_id']);
            $table->index(['cluster_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reddit_signals');
    }
};
