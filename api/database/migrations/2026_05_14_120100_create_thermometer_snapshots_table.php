<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('thermometer_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cluster_id')->constrained('news_clusters')->cascadeOnDelete();
            $table->decimal('thermometer', 5, 2);
            $table->decimal('coverage_component', 5, 2);
            $table->decimal('reddit_component', 5, 2);
            $table->decimal('recency_component', 5, 2);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['cluster_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thermometer_snapshots');
    }
};
