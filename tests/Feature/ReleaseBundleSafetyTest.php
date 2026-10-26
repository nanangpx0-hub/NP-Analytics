<?php

namespace Tests\Feature;

use App\Services\DatabaseBootstrapper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReleaseBundleSafetyTest extends TestCase
{
    private ?string $originalDatabase = null;

    private ?string $temporaryDatabase = null;

    protected function tearDown(): void
    {
        if ($this->originalDatabase !== null) {
            config(['database.connections.sqlite.database' => $this->originalDatabase]);
            DB::purge('sqlite');
        }

        if ($this->temporaryDatabase !== null) {
            @unlink($this->temporaryDatabase);
        }

        parent::tearDown();
    }

    public function test_schema_bootstraps_when_sqlite_file_does_not_exist(): void
    {
        $path = storage_path('framework/testing/boot-'.Str::uuid().'.sqlite');
        $this->temporaryDatabase = $path;
        $this->useDatabasePath($path);

        Artisan::spy();

        $this->assertTrue((new DatabaseBootstrapper)->ensureSchema());
        $this->assertFileExists($path);
    }

    public function test_in_memory_database_does_not_create_a_file(): void
    {
        $this->useDatabasePath(':memory:');

        (new DatabaseBootstrapper)->ensureDatabaseFileExists();

        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_release_bundle_excludes_credentials_database_and_logs(): void
    {
        $excludes = config('nativephp.cleanup_exclude_files');
        $envKeys = config('nativephp.cleanup_env_keys');

        foreach (['android-keystore', 'keys', 'dist', 'database/private', 'storage/logs'] as $path) {
            $this->assertContains($path, $excludes);
        }

        foreach ([
            'DB_DATABASE',
            'MAIL_PASSWORD',
            'AWS_SECRET_ACCESS_KEY',
            'GEMINI_API_KEY',
            'BPS_API_KEY',
            'SYNC_API_KEY',
            'ANDROID_KEYSTORE_PASSWORD',
            'ANDROID_KEY_PASSWORD',
        ] as $key) {
            $this->assertContains($key, $envKeys);
        }
    }

    private function useDatabasePath(string $path): void
    {
        $this->originalDatabase = config('database.connections.sqlite.database');
        config(['database.connections.sqlite.database' => $path]);
        DB::purge('sqlite');
    }
}
