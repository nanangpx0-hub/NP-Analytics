<?php

use App\Livewire\Dashboard;
use App\Livewire\IndicatorDetail;
use App\Livewire\IndicatorList;
use App\Livewire\SyncSettings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to dashboard
Route::redirect('/', '/dashboard');

// Dashboard Route
Route::get('/dashboard', Dashboard::class)->name('dashboard');

// Indicators Routes
Route::get('/indicators', IndicatorList::class)->name('indicators.index');
Route::get('/indicators/{id}', IndicatorDetail::class)->name('indicators.show');

// Category-specific indicators
Route::get('/indicators/category/{categoryId}', IndicatorList::class)
    ->name('indicators.category');

// Device activation and sync settings
Route::get('/settings/sync', SyncSettings::class)->name('sync.settings');

// Optional: API Routes for Mobile (if needed later)
// Route::prefix('api')->group(function () {
//     Route::get('/indicators', [App\Http\Controllers\Api\IndicatorController::class, 'index']);
//     Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
// });
