<?php

namespace App\Providers;

use App\Models\Indicator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
        if (config('database.default') === 'sqlite') {
            $this->configureSqlite();
        }

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

    /**
     * Terapkan pragma SQLite yang optimal untuk perangkat mobile
     * (WAL, NORMAL sync, temp store di memori, mmap 64 MB, cache 16 MB).
     */
    private function configureSqlite(): void
    {
        try {
            $pdo = DB::connection()->getPdo();

            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA temp_store = MEMORY');
            $pdo->exec('PRAGMA mmap_size = 67108864');
            $pdo->exec('PRAGMA cache_size = -16000');
        } catch (\Throwable $e) {
            logger()->warning('SQLite pragma setup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
