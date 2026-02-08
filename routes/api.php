<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('sync')
    ->middleware(['api', 'sync.key'])
    ->group(function () {
        Route::get('/pull', [SyncController::class, 'pull']);
        Route::post('/push', [SyncController::class, 'push']);
    });
