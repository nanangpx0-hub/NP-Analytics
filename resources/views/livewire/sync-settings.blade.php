<div class="mx-auto max-w-2xl space-y-5">
    <div>
        <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
        <h1 class="mt-2 text-2xl font-bold text-brand-navy">Pengaturan Sync</h1>
        <p class="mt-1 text-sm text-slate-600">Aktifkan perangkat dengan kode sekali pakai dari admin. Token disimpan terenkripsi di perangkat dan tidak pernah ditulis ke file .env.</p>
    </div>

    @if($message)
        <div role="status" class="rounded-lg border px-4 py-3 text-sm {{ $status === 'error' ? 'border-red-200 bg-red-50 text-red-700' : ($status === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-700') }}">
            {{ $message }}
        </div>
    @endif

    @if($this->conflicts->isNotEmpty())
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-amber-900">Konflik sinkronisasi ({{ $this->conflicts->count() }})</h2>
            <p class="mt-1 text-sm text-amber-800">Data ini berubah di perangkat dan di server. Pilih versi yang ingin dipertahankan.</p>

            <div class="mt-4 space-y-3">
                @foreach($this->conflicts as $conflict)
                    <div class="rounded-lg border border-amber-200 bg-white p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ $conflict->type_label }}</p>
                                <p class="font-semibold text-slate-800">{{ $conflict->title }}</p>
                                <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $conflict->record_uuid }}</p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Server v{{ $conflict->server_version }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" wire:click="resolveWithServer({{ $conflict->id }})" wire:loading.attr="disabled"
                                    class="rounded-lg bg-brand-navy px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60">
                                Pakai versi server
                            </button>
                            <button type="button" wire:click="resolveWithLocal({{ $conflict->id }})" wire:loading.attr="disabled"
                                    class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 disabled:opacity-60">
                                Pakai versi saya
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold text-brand-navy">Status perangkat</h2>

        @if($this->credential)
            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Server</dt>
                    <dd class="font-semibold break-all">{{ $this->credential->server_url }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nama perangkat</dt>
                    <dd class="font-semibold">{{ $this->credential->device_name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Diaktifkan</dt>
                    <dd class="font-semibold">{{ $this->credential->last_registered_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Izin token</dt>
                    <dd class="font-semibold">{{ implode(', ', (array) $this->credential->abilities) ?: '-' }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="syncNow" wire:loading.attr="disabled" wire:target="syncNow"
                        class="rounded-lg bg-brand-navy px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="syncNow">Sync sekarang</span>
                    <span wire:loading wire:target="syncNow">Menyinkronkan...</span>
                </button>
                <button type="button" wire:click="disconnect" wire:confirm="Hapus token perangkat dari perangkat ini?"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">
                    Putuskan perangkat
                </button>
            </div>
        @else
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">Perangkat belum diaktifkan. Minta admin membuat kode aktivasi di panel Perangkat.</p>
        @endif
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold text-brand-navy">Aktivasi perangkat</h2>
        <form wire:submit="register" class="mt-4 space-y-4">
            <div>
                <label for="server-url" class="block text-sm font-semibold text-slate-700">URL server</label>
                <input id="server-url" type="url" wire:model="serverUrl" placeholder="https://analitik.example.id"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-navy focus:outline-none">
                @error('serverUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="device-name" class="block text-sm font-semibold text-slate-700">Nama perangkat</label>
                <input id="device-name" type="text" wire:model="deviceName"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-navy focus:outline-none">
                @error('deviceName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="activation-code" class="block text-sm font-semibold text-slate-700">Kode aktivasi</label>
                <input id="activation-code" type="text" wire:model="activationCode" autocomplete="off" placeholder="NP-XXXX-XXXX-XXXX"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm uppercase focus:border-brand-navy focus:outline-none">
                @error('activationCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="register"
                    class="rounded-lg bg-brand-gold px-4 py-2 text-sm font-bold text-brand-navy disabled:opacity-60">
                <span wire:loading.remove wire:target="register">Aktifkan perangkat</span>
                <span wire:loading wire:target="register">Menghubungi server...</span>
            </button>
        </form>
    </section>
</div>
