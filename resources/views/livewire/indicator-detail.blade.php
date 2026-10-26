<div>
    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('indicators.index') }}" wire:navigate
           class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition-colors font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Daftar Indikator
        </a>
    </div>

    {{-- Main Card --}}
    <article aria-labelledby="indicator-title" class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
        {{-- Header --}}
        <header class="bg-brand-navy text-white p-4 sm:p-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1">
                    {{-- Category Badge --}}
                    @if($category)
                        <p class="mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-gold text-brand-navy text-xs font-bold rounded-full shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                {{ $category->name }}
                            </span>
                        </p>
                    @endif

                    {{-- Title --}}
                    <h1 id="indicator-title" class="text-2xl md:text-3xl font-bold mb-2">{{ $indicator->title }}</h1>

                    {{-- Year & Frequency --}}
                    <p class="text-slate-300 text-sm">
                        Data Tahun <time datetime="{{ $indicator->year }}" class="font-semibold text-white">{{ $indicator->year }}</time>
                        @if($indicator->frequency)
                            &bull; Frekuensi: <span class="capitalize">{{ $indicator->frequency }}</span>
                        @endif
                        @if($indicator->region_level)
                            &bull; Wilayah: <span class="capitalize">{{ $indicator->region_level }} Jember</span>
                        @endif
                    </p>
                </div>

                {{-- Trend Badge --}}
                @if($trendData['value'] !== null)
                    <div class="flex-shrink-0">
                        <p class="inline-flex items-center gap-2 px-4 py-2 rounded-lg shadow-sm
                            {{ $trendData['is_positive'] ? 'bg-green-500/20 text-green-300 border border-green-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }}">
                            @if($trendData['icon'] === 'arrow-up')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            @elseif($trendData['icon'] === 'arrow-down')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            @endif
                            <data value="{{ $trendData['value'] }}" class="text-lg font-bold">{{ $trendData['formatted'] }}</data>
                            <span class="text-xs opacity-80">({{ $indicator->is_higher_better ? 'Meningkat = Positif' : 'Menurun = Positif' }})</span>
                        </p>
                    </div>
                @endif
            </div>
        </header>

        {{-- Body --}}
        <div class="p-4 sm:p-6 space-y-6 sm:space-y-8">
            {{-- Value Display --}}
            <section aria-label="Nilai resmi indikator" class="p-4 sm:p-6 bg-slate-50 rounded-xl border border-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Nilai Resmi Indikator</p>
                <p class="flex items-baseline gap-2 flex-wrap">
                    <data value="{{ $indicator->value }}" class="text-4xl md:text-5xl font-extrabold text-brand-navy">
                        {{ number_format($indicator->value, ($indicator->value == intval($indicator->value) ? 0 : 2), ',', '.') }}
                    </data>
                    <span class="text-xl text-slate-600 font-semibold">{{ $indicator->unit }}</span>
                </p>
            </section>

            {{-- AI Insight --}}
            <x-ai-insight-panel
                :insight="$resolvedAiInsight"
                :configured="$geminiConfigured"
                heading-id="indicator-ai-heading"
            />

            {{-- Description --}}
            @if($indicator->description)
                <section aria-labelledby="indicator-description-heading">
                    <h2 id="indicator-description-heading" class="text-lg font-bold text-brand-navy mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Deskripsi &amp; Konteks Analisis
                    </h2>
                    <p class="text-slate-700 leading-relaxed bg-white p-4 rounded-lg border border-slate-100 shadow-sm">{{ $indicator->description }}</p>
                </section>
            @endif

            {{-- Historical Series Section (if exists) --}}
            @if(isset($history) && $history->count() > 1)
                <section aria-labelledby="indicator-history-heading">
                    <h2 id="indicator-history-heading" class="text-lg font-bold text-brand-navy mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Riwayat Perkembangan Indikator (Time-Series)
                    </h2>

                    {{-- Grafik time-series lokal (SVG, tanpa CDN) --}}
                    @if($chart)
                        <figure class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4"
                                data-testid="time-series-chart">
                            <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}"
                                 class="h-auto w-full" role="img"
                                 aria-label="Grafik time-series {{ $indicator->title }}">
                                <title>Grafik time-series {{ $indicator->title }} ({{ $indicator->unit }})</title>
                                <defs>
                                    <linearGradient id="npChartArea" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#d4a017" stop-opacity="0.28"></stop>
                                        <stop offset="100%" stop-color="#d4a017" stop-opacity="0.02"></stop>
                                    </linearGradient>
                                </defs>

                                @foreach($chart['grid'] as $line)
                                    <line x1="{{ $chart['left'] }}" y1="{{ $line['y'] }}"
                                          x2="{{ $chart['width'] - $chart['right'] }}" y2="{{ $line['y'] }}"
                                          stroke="#e2e8f0" stroke-dasharray="3 3"></line>
                                    <text x="{{ $chart['left'] - 8 }}" y="{{ $line['y'] + 4 }}"
                                          text-anchor="end" font-size="10" fill="#64748b">{{ $line['label'] }}</text>
                                @endforeach

                                <polygon points="{{ $chart['area'] }}" fill="url(#npChartArea)"></polygon>
                                <polyline points="{{ $chart['line'] }}" fill="none" stroke="#0f172a"
                                          stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"></polyline>

                                @foreach($chart['points'] as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}"
                                            r="{{ $point['current'] ? 5 : 4 }}"
                                            fill="{{ $point['current'] ? '#d4a017' : '#0f172a' }}"
                                            stroke="#ffffff" stroke-width="2">
                                        <title>{{ $point['year'] }}: {{ $point['label'] }} {{ $indicator->unit }}</title>
                                    </circle>
                                    <text x="{{ $point['x'] }}" y="{{ $chart['height'] - 16 }}"
                                          text-anchor="middle" font-size="10" fill="#475569">{{ $point['year'] }}</text>
                                @endforeach
                            </svg>
                            <figcaption class="mt-1 text-center text-[11px] text-slate-500">
                                Grafik dari data lokal &middot; {{ count($chart['points']) }} titik tahun
                            </figcaption>
                        </figure>
                    @endif

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                        @foreach($history as $item)
                            <a href="{{ route('indicators.show', $item->id) }}" wire:navigate
                               aria-current="{{ $item->id === $indicator->id ? 'page' : 'false' }}"
                               class="p-3 rounded-lg border transition-all text-center {{ $item->id === $indicator->id ? 'bg-amber-50 border-amber-400 ring-2 ring-amber-400/30' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }}">
                                <time datetime="{{ $item->year }}" class="text-xs font-bold {{ $item->id === $indicator->id ? 'text-amber-800' : 'text-slate-500' }} block mb-1">
                                    {{ $item->year }}
                                </time>
                                <data value="{{ $item->value }}" class="text-lg font-extrabold text-brand-navy block">
                                    {{ number_format($item->value, ($item->value == intval($item->value) ? 0 : 2), ',', '.') }}
                                </data>
                                <span class="text-[10px] text-slate-500 block truncate">{{ $item->unit }}</span>
                                @if($item->trend !== null)
                                    <span class="inline-block mt-1 text-[11px] font-semibold {{ $item->trend_color === 'green' ? 'text-green-600' : ($item->trend_color === 'red' ? 'text-red-600' : 'text-slate-500') }}">
                                        {{ $item->formatted_trend }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Metadata & Provenance Box --}}
            @if($indicator->source_document || $indicator->quality_note)
                <section aria-labelledby="indicator-metadata-heading" class="p-5 bg-sky-50 rounded-xl border border-sky-200">
                    <h2 id="indicator-metadata-heading" class="text-sm font-bold text-sky-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Metadata &amp; Sumber Data Resmi
                    </h2>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        @if($indicator->source_document)
                            <div>
                                <dt class="text-slate-500 text-xs">Dokumen Sumber:</dt>
                                <dd class="font-semibold text-slate-800">{{ $indicator->source_document }}</dd>
                            </div>
                        @endif
                        @if($indicator->source_table)
                            <div>
                                <dt class="text-slate-500 text-xs">Tabel / Rujukan:</dt>
                                <dd class="font-semibold text-slate-800">{{ $indicator->source_table }}</dd>
                            </div>
                        @endif
                        @if($indicator->quality_note)
                            <div class="md:col-span-2">
                                <dt class="text-slate-500 text-xs">Catatan Kualitas &amp; Verifikasi:</dt>
                                <dd class="text-slate-700">{{ $indicator->quality_note }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif

            {{-- Phenomena Section --}}
            @if($phenomenaStats['total'] > 0)
                <section aria-labelledby="indicator-phenomena-heading" class="border-t border-slate-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 id="indicator-phenomena-heading" class="text-lg font-bold text-brand-navy flex items-center gap-2">
                            <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Fenomena Terkait &amp; Analisis Kebijakan
                        </h2>
                        <div class="flex items-center gap-3 text-xs font-semibold">
                            @if($phenomenaStats['positive'] > 0)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                                    {{ $phenomenaStats['positive'] }} Positif
                                </span>
                            @endif
                            @if($phenomenaStats['negative'] > 0)
                                <span class="inline-flex items-center gap-1 text-red-700 bg-red-100 px-2.5 py-1 rounded-full">
                                    {{ $phenomenaStats['negative'] }} Negatif
                                </span>
                            @endif
                            @if($phenomenaStats['neutral'] > 0)
                                <span class="inline-flex items-center gap-1 text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full">
                                    {{ $phenomenaStats['neutral'] }} Netral
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Phenomena List --}}
                    <ul class="space-y-4">
                        @foreach($phenomena as $phenomenon)
                            @php
                                $impactStyles = match ($phenomenon->impact) {
                                    'positive' => [
                                        'bg' => 'bg-green-50/70',
                                        'border' => 'border-green-500',
                                        'badge' => 'bg-green-100 text-green-800',
                                        'label' => 'Dampak Positif',
                                    ],
                                    'negative' => [
                                        'bg' => 'bg-red-50/70',
                                        'border' => 'border-red-500',
                                        'badge' => 'bg-red-100 text-red-800',
                                        'label' => 'Dampak Negatif / Risiko',
                                    ],
                                    default => [
                                        'bg' => 'bg-slate-50',
                                        'border' => 'border-slate-400',
                                        'badge' => 'bg-slate-200 text-slate-800',
                                        'label' => 'Netral / Konteks',
                                    ],
                                };
                            @endphp
                            <li>
                                <article aria-label="Fenomena: {{ $phenomenon->title }}" class="p-4 rounded-xl border-l-4 {{ $impactStyles['bg'] }} {{ $impactStyles['border'] }} shadow-sm">
                                    <header class="flex items-start justify-between gap-3 mb-2">
                                        <h3 class="font-bold text-slate-900 text-base">{{ $phenomenon->title }}</h3>
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $impactStyles['badge'] }} flex-shrink-0">
                                            {{ $impactStyles['label'] }}
                                        </span>
                                    </header>
                                    <p class="text-sm text-slate-700 leading-relaxed">{{ $phenomenon->description }}</p>
                                    @if(!empty($phenomenon->source))
                                        <footer class="text-xs text-slate-500 mt-2 font-medium">
                                            Sumber Analisis: <cite class="not-italic">{{ $phenomenon->source }}</cite>
                                        </footer>
                                    @endif
                                </article>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Show More Button --}}
                    @if($hasMorePhenomena)
                        <div class="mt-4 text-center">
                            <button type="button" wire:click="togglePhenomena"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-navy bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                @if($showAllPhenomena)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                    Tampilkan Lebih Sedikit
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    Tampilkan Semua ({{ $phenomenaStats['total'] }})
                                @endif
                            </button>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </article>
</div>
