<?php

namespace App\Livewire;

use App\Services\SyncException;
use App\Services\SyncService;
use Carbon\Carbon;
use Livewire\Component;

class SyncButton extends Component
{
    public string $status = 'idle';

    public ?string $message = null;

    public ?string $lastSync = null;

    public ?int $lastServerVersion = null;

    public function mount(SyncService $syncService): void
    {
        $this->refreshState($syncService);
    }

    public function syncNow(SyncService $syncService): void
    {
        if ($this->status === 'syncing') {
            return;
        }

        $this->message = null;
        $this->status = 'syncing';

        try {
            $result = $syncService->sync();
            $this->refreshState($syncService);
            $this->message = $this->formatSummary($result);
            $this->status = (! empty($result['conflicts']) || ! empty($result['errors']))
                ? 'warning'
                : 'success';
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

    private function refreshState(SyncService $syncService): void
    {
        $this->lastSync = $syncService->getLastSyncAt()?->toIso8601String();
        $this->lastServerVersion = $syncService->getLastServerVersion();
    }

    public function getLastSyncLabelProperty(): string
    {
        if (! $this->lastSync) {
            return 'Belum pernah sync';
        }

        try {
            return Carbon::parse($this->lastSync)
                ->timezone(config('app.timezone'))
                ->format('d M Y H:i');
        } catch (\Throwable $exception) {
            return 'Tanggal tidak valid';
        }
    }

    public function render()
    {
        return view('livewire.sync-button');
    }

    private function formatSummary(array $result): string
    {
        $pushed = $result['pushed'] ?? ['categories' => 0, 'indicators' => 0, 'phenomena' => 0];
        $pulled = $result['pulled'] ?? ['categories' => 0, 'indicators' => 0, 'phenomena' => 0];

        $parts = [
            "Push K{$pushed['categories']} I{$pushed['indicators']} F{$pushed['phenomena']}",
            "Pull K{$pulled['categories']} I{$pulled['indicators']} F{$pulled['phenomena']}",
        ];

        $conflicts = isset($result['conflicts']) ? count((array) $result['conflicts']) : 0;
        if ($conflicts > 0) {
            $parts[] = "Conflict {$conflicts}";
        }

        $errors = isset($result['errors']) ? count((array) $result['errors']) : 0;
        if ($errors > 0) {
            $parts[] = "Error {$errors}";
        }

        return implode(' '."\u{00B7}".' ', $parts);
    }
}
