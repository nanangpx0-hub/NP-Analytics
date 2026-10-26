<?php

namespace App\Livewire;

use App\Models\DeviceCredential;
use App\Models\SyncConflict;
use App\Services\SyncException;
use App\Services\SyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class SyncSettings extends Component
{
    public string $serverUrl = '';

    public string $activationCode = '';

    public string $deviceName = '';

    public ?string $message = null;

    public string $status = 'idle';

    public function mount(): void
    {
        $credential = DeviceCredential::active();
        $this->serverUrl = $credential->server_url ?? '';
        $this->deviceName = $credential->device_name ?? $this->defaultDeviceName();
    }

    public function register(): void
    {
        $data = $this->validate([
            'serverUrl' => ['required', 'url:http,https', 'max:255'],
            'activationCode' => ['required', 'string', 'max:40'],
            'deviceName' => ['required', 'string', 'max:120'],
        ]);

        $this->status = 'working';
        $this->message = null;
        $deviceUuid = $this->deviceUuid();

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post(rtrim($data['serverUrl'], '/').'/api/devices/register', [
                    'activation_code' => $data['activationCode'],
                    'device_name' => $data['deviceName'],
                    'device_uuid' => $deviceUuid,
                    'platform' => 'android',
                ]);
        } catch (\Throwable $exception) {
            $this->status = 'error';
            $this->message = 'Server tidak dapat dihubungi. Periksa URL dan koneksi internet.';

            return;
        }

        if ($response->status() === 422) {
            $this->status = 'error';
            $this->message = (string) ($response->json('message') ?? 'Kode aktivasi tidak valid atau sudah kedaluwarsa.');

            return;
        }

        if (! $response->successful() || ! is_string($response->json('token'))) {
            $this->status = 'error';
            $this->message = 'Aktivasi perangkat gagal. Hubungi admin untuk memastikan server dan kode aktivasi.';

            return;
        }

        DeviceCredential::query()->delete();
        DeviceCredential::create([
            'server_url' => rtrim($data['serverUrl'], '/'),
            'device_uuid' => $deviceUuid,
            'device_name' => $data['deviceName'],
            'token' => $response->json('token'),
            'abilities' => (array) $response->json('abilities', []),
            'last_registered_at' => now(),
        ]);

        $this->activationCode = '';
        $this->status = 'success';
        $this->message = 'Perangkat berhasil diaktifkan. Sinkronisasi dan analisis AI server siap digunakan.';
        $this->dispatch('device-linked');
    }

    public function disconnect(): void
    {
        DeviceCredential::query()->delete();

        $this->status = 'success';
        $this->message = 'Perangkat diputuskan. Token dihapus dari penyimpanan lokal.';
        $this->dispatch('device-unlinked');
    }

    public function syncNow(SyncService $syncService): void
    {
        if ($this->status === 'working') {
            return;
        }

        $this->status = 'working';
        $this->message = null;

        try {
            $result = $syncService->sync();
            $pushed = $result['pushed'] ?? [];
            $pulled = $result['pulled'] ?? [];
            $conflicts = count((array) ($result['conflicts'] ?? []));
            $errors = count((array) ($result['errors'] ?? []));

            $this->status = $errors === 0 && $conflicts === 0 ? 'success' : 'warning';
            $this->message = sprintf(
                'Sync selesai. Push K%d I%d F%d · Pull K%d I%d F%d%s%s',
                $pushed['categories'] ?? 0,
                $pushed['indicators'] ?? 0,
                $pushed['phenomena'] ?? 0,
                $pulled['categories'] ?? 0,
                $pulled['indicators'] ?? 0,
                $pulled['phenomena'] ?? 0,
                $conflicts > 0 ? " · Konflik {$conflicts}" : '',
                $errors > 0 ? " · Error {$errors}" : '',
            );
        } catch (SyncException $exception) {
            $this->status = 'error';
            $this->message = $exception->getMessage();
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Unexpected sync failure', [
                'error' => $exception->getMessage(),
            ]);
            $this->status = 'error';
            $this->message = 'Sinkronisasi gagal karena kendala tak terduga. Coba lagi.';
        }
    }

    public function resolveWithServer(int $conflictId, SyncService $syncService): void
    {
        $this->resolveConflict($conflictId, $syncService, true);
    }

    public function resolveWithLocal(int $conflictId, SyncService $syncService): void
    {
        $this->resolveConflict($conflictId, $syncService, false);
    }

    private function resolveConflict(int $conflictId, SyncService $syncService, bool $useServer): void
    {
        $conflict = SyncConflict::query()->find($conflictId);

        if (! $conflict) {
            $this->status = 'error';
            $this->message = 'Konflik sudah tidak tersedia. Muat ulang halaman.';

            return;
        }

        try {
            if ($useServer) {
                $syncService->resolveConflictWithServer($conflict);
                $this->message = 'Versi server diterapkan pada perangkat.';
            } else {
                $syncService->resolveConflictWithLocal($conflict);
                $this->message = 'Versi lokal disimpan dan akan dikirim pada sync berikutnya.';
            }

            $this->status = 'success';
        } catch (SyncException $exception) {
            $this->status = 'error';
            $this->message = $exception->getMessage();
        }
    }

    public function getCredentialProperty(): ?DeviceCredential
    {
        return DeviceCredential::active();
    }

    public function getConflictsProperty()
    {
        return SyncConflict::query()
            ->unresolved()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.sync-settings')->layout('layouts.app', [
            'title' => 'Pengaturan Sync',
        ]);
    }

    private function deviceUuid(): string
    {
        $credential = DeviceCredential::active();

        return $credential->device_uuid ?? (string) Str::uuid();
    }

    private function defaultDeviceName(): string
    {
        return 'NP Analytics Android';
    }
}
