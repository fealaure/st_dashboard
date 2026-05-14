<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news_clusters', function (Blueprint $table): void {
            $table->integer('reddit_upvotes')->default(0)->after('thermometer');
            $table->integer('reddit_comments')->default(0)->after('reddit_upvotes');
            $table->timestamp('reddit_synced_at')->nullable()->after('reddit_comments');
        });
    }

    public function down(): void
    {
        Schema::table('news_clusters', function (Blueprint $table): void {
            $table->dropColumn(['reddit_upvotes', 'reddit_comments', 'reddit_synced_at']);
        });
    }
};
