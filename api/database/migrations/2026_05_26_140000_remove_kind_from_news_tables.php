<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('news_clusters', 'kind')) {
            Schema::table('news_clusters', function (Blueprint $table): void {
                $table->dropIndex(['kind']);
                $table->dropColumn('kind');
            });
        }

        if (Schema::hasColumn('news_items', 'kind')) {
            Schema::table('news_items', function (Blueprint $table): void {
                $table->dropIndex(['kind']);
                $table->dropColumn('kind');
            });
        }

        if (Schema::hasColumn('sources', 'kind')) {
            Schema::table('sources', function (Blueprint $table): void {
                $table->dropIndex(['kind']);
                $table->dropColumn('kind');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sources', 'kind')) {
            Schema::table('sources', function (Blueprint $table): void {
                $table->string('kind', 16)->default('news')->after('name')->index();
            });
        }

        if (! Schema::hasColumn('news_items', 'kind')) {
            Schema::table('news_items', function (Blueprint $table): void {
                $table->string('kind', 16)->default('news')->after('source_id')->index();
            });
        }

        if (! Schema::hasColumn('news_clusters', 'kind')) {
            Schema::table('news_clusters', function (Blueprint $table): void {
                $table->string('kind', 16)->default('news')->after('simhash')->index();
            });
        }
    }
};
