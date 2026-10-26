<?php

namespace Tests\Feature;

use App\Livewire\SyncSettings;
use App\Models\DeviceCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_registration_stores_encrypted_token_and_never_plaintext(): void
    {
        Http::fake([
            'sync.test/api/devices/register' => Http::response([
                'token' => 'plain-device-token',
                'abilities' => ['sync:pull', 'sync:push', 'ai:insight'],
                'device' => ['uuid' => '11111111-1111-4111-8111-111111111111', 'name' => 'HP Jember'],
            ], 201),
        ]);

        Livewire::test(SyncSettings::class)
            ->set('serverUrl', 'https://sync.test')
            ->set('activationCode', 'NP-AAAA-BBBB-CCCC')
            ->set('deviceName', 'HP Jember')
            ->call('register')
            ->assertSet('status', 'success');

        $credential = DeviceCredential::active();
        $this->assertNotNull($credential);
        $this->assertSame('plain-device-token', $credential->token);

        $raw = DB::table('device_credentials')->value('token');
        $this->assertNotSame('plain-device-token', $raw);
        $this->assertStringNotContainsString('plain-device-token', (string) $raw);
    }

    public function test_invalid_activation_code_shows_server_message(): void
    {
        Http::fake([
            'sync.test/api/devices/register' => Http::response([
                'code' => 'invalid_activation_code',
                'message' => 'Kode aktivasi tidak valid, sudah digunakan, atau kedaluwarsa.',
            ], 422),
        ]);

        Livewire::test(SyncSettings::class)
            ->set('serverUrl', 'https://sync.test')
            ->set('activationCode', 'NP-SALAH')
            ->set('deviceName', 'HP Jember')
            ->call('register')
            ->assertSet('status', 'error')
            ->assertSee('Kode aktivasi tidak valid');

        $this->assertSame(0, DeviceCredential::count());
    }

    public function test_disconnect_removes_stored_token(): void
    {
        DeviceCredential::create([
            'server_url' => 'https://sync.test',
            'device_uuid' => '11111111-1111-4111-8111-111111111111',
            'device_name' => 'HP Jember',
            'token' => 'token',
            'abilities' => ['sync:pull'],
        ]);

        Livewire::test(SyncSettings::class)
            ->call('disconnect')
            ->assertSet('status', 'success');

        $this->assertSame(0, DeviceCredential::count());
    }
}
