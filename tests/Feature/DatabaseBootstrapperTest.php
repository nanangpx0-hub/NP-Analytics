<?php

namespace Tests\Feature;

use App\Services\DatabaseBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class DatabaseBootstrapperTest extends TestCase
{
    use RefreshDatabase;

    private function resetBootFlag(): void
    {
        $property = (new ReflectionClass(DatabaseBootstrapper::class))->getProperty('booted');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }

    public function test_schema_migration_runs_when_app_version_code_changes(): void
    {
        config(['nativephp.version_code' => 77]);
        DB::table('sync_states')->where('key', DatabaseBootstrapper::VERSION_KEY)->delete();

        Artisan::spy();

        $bootstrapper = new DatabaseBootstrapper;

        $this->assertTrue($bootstrapper->ensureSchema());
        $this->assertSame(
            '77',
            DB::table('sync_states')->where('key', DatabaseBootstrapper::VERSION_KEY)->value('value'),
        );

        // Versi sama: migrasi tidak boleh dijalankan lagi.
        $this->assertFalse($bootstrapper->ensureSchema());

        Artisan::shouldHaveReceived('call')
            ->with('migrate', ['--force' => true])
            ->once();
    }

    public function test_schema_migration_runs_when_stored_version_is_outdated(): void
    {
        config(['nativephp.version_code' => 78]);
        DB::table('sync_states')->updateOrInsert(
            ['key' => DatabaseBootstrapper::VERSION_KEY],
            ['value' => '76', 'created_at' => now(), 'updated_at' => now()],
        );

        Artisan::spy();

        $this->assertTrue((new DatabaseBootstrapper)->ensureSchema());

        $this->assertSame(
            '78',
            DB::table('sync_states')->where('key', DatabaseBootstrapper::VERSION_KEY)->value('value'),
        );
        Artisan::shouldHaveReceived('call')
            ->with('migrate', ['--force' => true])
            ->once();
    }

    public function test_bootstrap_runs_only_once_even_when_called_repeatedly(): void
    {
        config(['nativephp.version_code' => 79]);
        DB::table('sync_states')->where('key', DatabaseBootstrapper::VERSION_KEY)->delete();
        DB::table('indicators')->delete();

        Artisan::spy();

        $this->resetBootFlag();

        $bootstrapper = new DatabaseBootstrapper;
        $bootstrapper->bootstrap();
        $bootstrapper->bootstrap();

        Artisan::shouldHaveReceived('call')
            ->with('migrate', ['--force' => true])
            ->once();
        Artisan::shouldHaveReceived('call')
            ->with('db:seed', ['--force' => true])
            ->once();

        $this->resetBootFlag();
    }

    public function test_bootstrap_skips_seeding_when_local_data_exists(): void
    {
        config(['nativephp.version_code' => 80]);
        DB::table('sync_states')->updateOrInsert(
            ['key' => DatabaseBootstrapper::VERSION_KEY],
            ['value' => '80', 'created_at' => now(), 'updated_at' => now()],
        );

        $category = \App\Models\Category::create(['name' => 'Ekonomi', 'icon' => null]);
        $category->indicators()->create([
            'title' => 'PDRB',
            'value' => 5.47,
            'unit' => '%',
            'year' => 2025,
            'is_higher_better' => true,
        ]);

        Artisan::spy();

        $this->resetBootFlag();
        (new DatabaseBootstrapper)->bootstrap();

        Artisan::shouldNotHaveReceived('call');

        $this->resetBootFlag();
    }
}
