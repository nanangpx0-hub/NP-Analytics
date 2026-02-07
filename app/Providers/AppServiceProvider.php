<?php

namespace App\Providers;

use App\Models\Indicator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (config('database.default') !== 'sqlite') {
            return;
        }

        try {
            if (! Schema::hasTable('migrations')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (Schema::hasTable('indicators') && ! Indicator::query()->exists()) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            logger()->warning('Auto database setup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
