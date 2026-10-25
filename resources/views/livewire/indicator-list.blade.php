<div>
    {{-- Header --}}
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Daftar Indikator</h1>
        <p class="text-sm text-slate-600">Indikator statistik Kabupaten Jember</p>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-4 mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="search" inputmode="search" wire:model.live.debounce.300ms="search"
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Cari indikator...">
                </div>
            </div>

            {{-- Category Filter --}}
            <div class="flex gap-3">
                <select wire:model.live="categoryId"
                    class="flex-1 sm:w-52 px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="selectedYear"
                    class="w-24 sm:w-32 px-3 py-2.5 text-sm border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="all">Tahun</option>
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Results Count --}}
    <div class="mb-3 flex items-center justify-between text-sm text-slate-600">
        <p>
            <span class="font-bold text-slate-900">{{ $indicators->total() }}</span> indikator
            @if($categoryId)
                &bull; {{ $categories->find($categoryId)?->name }}
            @endif
            @if($selectedYear && $selectedYear !== 'all')
                &bull; {{ $selectedYear }}
            @endif
        </p>
        <span wire:loading wire:target="search, categoryId, selectedYear" class="text-xs text-amber-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 00-12 12h4z"></path>
            </svg>
            memuat...
        </span>
    </div>

    {{-- Indicators Grid --}}
    @if($indicators->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h3 class="text-base font-semibold text-slate-700 mb-1">Tidak ada indikator</h3>
            <p class="text-sm text-slate-500">Coba ubah kata kunci atau filter.</p>
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @foreach($indicators as $indicator)
                <x-indicator-card :indicator="$indicator" />
            @endforeach
        </div>
        <div class="mt-5">
            {{ $indicators->links() }}
        </div>
    @endif
</div>
