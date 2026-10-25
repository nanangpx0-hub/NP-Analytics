{{-- Tombol sinkronisasi kompak untuk navbar --}}
<div class="relative flex items-center">
    <button type="button" wire:click="syncNow" wire:target="syncNow"
        title="Sinkron dengan server (terakhir: {{ $this->lastSyncLabel }})"
        class="relative grid h-10 w-10 place-items-center rounded-full text-white bg-white/10 active:bg-white/25 hover:bg-white/20 transition disabled:opacity-60"
        wire:loading.attr="disabled" wire:target="syncNow">
        {{-- Icon idle --}}
        <svg class="h-5 w-5" wire:loading.remove wire:target="syncNow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        {{-- Spinner saat sync --}}
        <svg class="h-5 w-5 animate-spin" wire:loading wire:target="syncNow" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 00-12 12h4z"></path>
        </svg>
    </button>

    @if($message)
        <div class="absolute top-12 right-0 z-[70] max-w-56 rounded-lg border px-3 py-2 text-xs shadow-xl {{ $status === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
            {{ $message }}
        </div>
    @endif
</div>
