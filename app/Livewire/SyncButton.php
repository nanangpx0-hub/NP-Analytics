<?php

namespace App\Livewire;

use App\Services\SyncService;
use Carbon\Carbon;
use Livewire\Component;

class SyncButton extends Component
{
    public string $status = 'idle';
    public ?string $message = null;
    public ?string $lastSync = null;

    public function mount(SyncService $syncService): void
    {
        $lastSync = $syncService->getLastSyncTime();
        $this->lastSync = $lastSync?->toIso8601String();
    }

    public function syncNow(SyncService $syncService): void
    {
        $this->message = null;
        $this->status = 'syncing';

        try {
            $result = $syncService->sync();
            $this->status = 'success';
            $this->lastSync = $result['server_time'] ?? now()->toIso8601String();
            $this->message = $this->formatSummary($result);
        } catch (\Throwable $exception) {
            $this->status = 'error';
            $this->message = $exception->getMessage();
        }
    }

    public function getLastSyncLabelProperty(): string
    {
        if (!$this->lastSync) {
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

        return implode(' · ', $parts);
    }
}
