<?php

namespace App\Providers;

use App\Services\DatabaseBootstrapper;
use Illuminate\Support\Facades\DB;
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
        // Composer dump-autoload di folder bundel NativePHP menjalankan
        // package:discover. Jangan inisialisasi SQLite di proses itu agar
        // database kosong tidak ikut terpaket ke APK.
        $packageDiscovery = $this->app->runningInConsole()
            && in_array('package:discover', $_SERVER['argv'] ?? [], true);

        if (! $packageDiscovery && config('database.default') === 'sqlite') {
            app(DatabaseBootstrapper::class)->ensureDatabaseFileExists();
            $this->configureSqlite();
        }

        if ($this->app->runningInConsole()) {
            return;
        }

        if (config('database.default') !== 'sqlite') {
            return;
        }

        app(DatabaseBootstrapper::class)->bootstrap();
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
