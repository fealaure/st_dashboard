<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('igdb_id')->unique();
            $table->string('name');
            $table->string('slug', 200)->nullable();
            $table->text('summary')->nullable();
            $table->string('cover_url', 500)->nullable();
            $table->integer('hype')->default(0);
            $table->date('release_date')->nullable()->index();
            $table->json('platforms')->nullable();
            $table->json('publishers')->nullable();
            $table->string('igdb_url', 500)->nullable();
            $table->timestamp('last_synced_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
