<div>
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Daftar Indikator</h1>
            <p class="text-slate-600">Jelajahi semua indikator statistik</p>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-4">
                {{-- Search --}}
                <div class="flex-1">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="search" 
                            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            placeholder="Cari indikator...">
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="sm:w-48">
                    <select wire:model.live="categoryId" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Year Filter --}}
                <div class="sm:w-32">
                    <select wire:model.live="selectedYear" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        @for($year = date('Y'); $year >= 2020; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- Results Count --}}
        <div class="mb-4">
            <p class="text-sm text-slate-600">
                Menampilkan <span class="font-semibold">{{ $indicators->count() }}</span> indikator
                @if($categoryId)
                    @php $cat = $categories->find($categoryId) @endphp
                    dari kategori <span class="font-semibold">{{ $cat?->name }}</span>
                @endif
            </p>
        </div>

        {{-- Indicators Grid --}}
        @if($indicators->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-700 mb-2">Tidak ada indikator</h3>
                <p class="text-slate-500">Tidak ditemukan indikator dengan filter yang dipilih.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($indicators as $indicator)
                    <x-indicator-card :indicator="$indicator" />
                @endforeach
            </div>
        @endif
    </div>
</div>

