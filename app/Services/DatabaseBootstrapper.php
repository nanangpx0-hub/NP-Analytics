<?php

namespace App\Services;

use App\Models\Indicator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Menyiapkan database lokal saat aplikasi boot. Migrasi hanya dijalankan bila
 * tabel migrations belum ada atau NATIVEPHP_APP_VERSION_CODE berubah, sehingga
 * pembaruan APK otomatis menerapkan migrasi baru tepat satu kali per versi.
 */
class DatabaseBootstrapper
{
    public const VERSION_KEY = 'app_version_code';

    private static bool $booted = false;

    public function bootstrap(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        try {
            $this->ensureDatabaseFileExists();
            $this->ensureSchema();
            $this->seedIfEmpty();
        } catch (Throwable $exception) {
            logger()->warning('Auto database setup failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Instalasi baru tidak lagi membawa database.sqlite di dalam APK, sehingga
     * file SQLite harus dibuat sebelum koneksi/migrasi pertama.
     */
    public function ensureDatabaseFileExists(): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}", []);

        if (($config['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $path = $config['database'] ?? null;

        if (! is_string($path) || $path === '' || $path === ':memory:' || str_contains($path, 'mode=memory')) {
            return;
        }

        if (is_file($path)) {
            return;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        touch($path);
    }

    public function ensureSchema(): bool
    {
        $this->ensureDatabaseFileExists();

        $version = (string) config('nativephp.version_code', 1);
        $hasMigrations = Schema::hasTable('migrations');
        $stored = Schema::hasTable('sync_states')
            ? DB::table('sync_states')->where('key', self::VERSION_KEY)->value('value')
            : null;

        if ($hasMigrations && (string) $stored === $version) {
            return false;
        }

        Artisan::call('migrate', ['--force' => true]);
        $this->recordVersion($version);

        return true;
    }

    public function seedIfEmpty(): void
    {
        if (Schema::hasTable('indicators') && ! Indicator::query()->exists()) {
            Artisan::call('db:seed', ['--force' => true]);
        }
    }

    private function recordVersion(string $version): void
    {
        if (! Schema::hasTable('sync_states')) {
            return;
        }

        DB::table('sync_states')->updateOrInsert(
            ['key' => self::VERSION_KEY],
            ['value' => $version, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
