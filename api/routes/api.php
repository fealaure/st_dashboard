<?php

declare(strict_types=1);

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('news', [NewsController::class, 'index']);
});
