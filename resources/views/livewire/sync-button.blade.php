<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-800">Sinkronisasi Data</div>
            <div class="text-xs text-slate-500">Terakhir sync: {{ $this->lastSyncLabel }}</div>
            @if($message)
                <div class="mt-1 text-xs {{ $status === 'error' ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $message }}
                </div>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="syncNow"
                wire:loading.attr="disabled"
                wire:target="syncNow"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-navy px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy/90 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg class="h-4 w-4 animate-spin" wire:loading wire:target="syncNow" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 00-12 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="syncNow">Sync</span>
                <span wire:loading wire:target="syncNow">Menyinkronkan...</span>
            </button>
            @if($status === 'success')
                <span class="text-xs font-medium text-emerald-600">Berhasil</span>
            @elseif($status === 'error')
                <span class="text-xs font-medium text-red-600">Gagal</span>
            @elseif($status === 'syncing')
                <span class="text-xs font-medium text-slate-500">Proses</span>
            @endif
        </div>
    </div>
</div>
