<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_clusters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('simhash')->index();
            $table->string('canonical_title', 500);
            $table->text('canonical_url');
            $table->decimal('thermometer', 5, 2)->default(0.00)->index();
            $table->timestamp('thermometer_updated_at')->nullable();
            $table->timestamp('first_seen_at')->useCurrent()->index();
            $table->timestamp('last_seen_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_clusters');
    }
};
