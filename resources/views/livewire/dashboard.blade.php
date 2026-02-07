<div>
    {{-- Dashboard Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard Statistik</h1>
                <p class="text-slate-600">Pantau indikator statistik daerah secara real-time</p>
            </div>

            {{-- Year Filter --}}
            <div>
                <select wire:model.live="selectedYear" 
                    class="px-4 py-2 bg-white border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Categories --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-brand-navy rounded-lg">
                    <svg class="w-6 h-6 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Kategori</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['total_categories'] }}</p>
                </div>
            </div>
        </div>

        {{-- Total Indicators --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-brand-navy rounded-lg">
                    <svg class="w-6 h-6 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Indikator</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['total_indicators'] }}</p>
                </div>
            </div>
        </div>

        {{-- Positive Trends --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Trend Positif</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['positive_trends'] }}</p>
                </div>
            </div>
        </div>

        {{-- Negative Trends --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Trend Negatif</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['negative_trends'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Categories with Indicators --}}
    <div class="space-y-6">
        @foreach($categories as $category)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- Category Header --}}
                <div class="bg-brand-navy px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="text-brand-gold">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </span>
                        <h2 class="text-lg font-semibold text-white">{{ $category->name }}</h2>
                        <span class="ml-auto px-3 py-1 bg-brand-gold text-brand-navy text-sm font-medium rounded-full">
                            {{ $category->indicators->count() }} indikator
                        </span>
                    </div>
                </div>

                {{-- Indicators Grid --}}
                <div class="p-6">
                    @if($category->indicators->isEmpty())
                        <p class="text-slate-500 text-center py-4">Belum ada indikator untuk tahun {{ $selectedYear }}</p>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($category->indicators as $indicator)
                                <x-indicator-card :indicator="$indicator" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
