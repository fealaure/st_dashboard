<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table): void {
            $table->foreignId('cluster_id')
                ->nullable()
                ->after('source_id')
                ->constrained('news_clusters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table): void {
            $table->dropForeign(['cluster_id']);
            $table->dropColumn('cluster_id');
        });
    }
};
