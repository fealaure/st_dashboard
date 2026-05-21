<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
