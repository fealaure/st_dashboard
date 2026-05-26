<?php

declare(strict_types=1);

use App\Http\Controllers\GuidesController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReleasesController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('news', [NewsController::class, 'index']);
    Route::get('news/{cluster}/snapshots', [NewsController::class, 'snapshots'])
        ->whereNumber('cluster');
    Route::get('guides', [GuidesController::class, 'index']);
    Route::get('releases', [ReleasesController::class, 'index']);
});
