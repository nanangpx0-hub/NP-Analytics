@props([
    'insight' => null,
    'action' => 'generateAiInsight',
    'configured' => false,
    'headingId' => 'ai-insight-heading',
])

@php
    $sourceLabels = [
        'gemini' => 'Gemini',
        'cache' => 'Tersimpan offline',
        'local' => 'Analisis lokal',
    ];
    $sourceClasses = [
        'gemini' => 'bg-amber-100 text-amber-800 border-amber-300',
        'cache' => 'bg-sky-100 text-sky-800 border-sky-300',
        'local' => 'bg-slate-100 text-slate-700 border-slate-300',
    ];
    $source = $insight['source'] ?? null;
    $sourceLabel = $sourceLabels[$source] ?? null;
@endphp

<section aria-labelledby="{{ $headingId }}" class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-sky-50 shadow-sm overflow-hidden">
    <header class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-brand-navy text-white sm:px-6">
        <div class="min-w-0">
            <h2 id="{{ $headingId }}" class="flex items-center gap-2 text-base font-bold sm:text-lg">
                <svg class="w-5 h-5 text-brand-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
                Ringkasan &amp; Rekomendasi AI
            </h2>
            <p class="mt-0.5 text-xs text-slate-300">Powered by Google Gemini &bull; analisis kebijakan daerah</p>
        </div>

        <div class="flex items-center gap-2">
            @if($sourceLabel)
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $sourceClasses[$source] ?? 'bg-slate-100 text-slate-700 border-slate-300' }}">
                    {{ $sourceLabel }}
                </span>
            @endif

            <button type="button"
                    wire:click="{{ $action }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $action }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-gold px-3 py-2 text-xs font-bold text-brand-navy shadow-sm transition hover:bg-amber-400 disabled:opacity-60">
                <svg wire:loading.remove wire:target="{{ $action }}" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <svg wire:loading wire:target="{{ $action }}" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 00-12 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="{{ $action }}">{{ $insight ? 'Perbarui Analisis' : ($configured ? 'Analisis Gemini' : 'Analisis Offline') }}</span>
                <span wire:loading wire:target="{{ $action }}" aria-live="polite">Menganalisis...</span>
            </button>
        </div>
    </header>

    <div class="space-y-4 p-4 sm:p-6">
        @if($insight)
            @if(!empty($insight['notice']))
                <p role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    {{ $insight['notice'] }}
                </p>
            @endif

            <div class="grid gap-4 lg:grid-cols-2">
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-brand-navy">Ringkasan Eksekutif</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $insight['summary'] }}</p>
                </article>

                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-brand-navy">Analisis Akar Masalah</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $insight['root_cause'] }}</p>
                </article>
            </div>

            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-brand-navy">Rekomendasi Kebijakan Daerah</h3>
                <ul class="mt-2 space-y-2 text-sm leading-relaxed text-slate-700">
                    @forelse($insight['recommendations'] as $recommendation)
                        <li class="flex gap-2">
                            <span aria-hidden="true" class="mt-0.5 text-brand-gold font-bold">&bull;</span>
                            <span>{{ $recommendation }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500">Rekomendasi belum tersedia.</li>
                    @endforelse
                </ul>
            </article>

            @if(!empty($insight['generated_at_label']))
                <p class="text-xs text-slate-500">
                    Dianalisis {{ $insight['generated_at_label'] }}
                    @if(!empty($insight['model']))
                        dengan model {{ $insight['model'] }}
                    @endif
                    &bull; hasil disimpan di SQLite perangkat dan tetap tersedia saat offline.
                </p>
            @endif
        @else
            <div>
                <h3 class="text-sm font-bold text-brand-navy">Analisis cerdas untuk pengambilan keputusan</h3>
                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                    Gemini merangkum data indikator, histori time-series, dan fenomena lokal menjadi ringkasan eksekutif,
                    analisis akar masalah, serta rekomendasi kebijakan daerah. Hasil analisis disimpan di SQLite perangkat
                    sehingga tetap dapat dibaca saat offline.
                </p>
                @if(!$configured)
                    <p class="mt-2 text-xs text-slate-500">GEMINI_API_KEY belum diatur. Tombol analisis akan menghasilkan rangkuman lokal berbasis data.</p>
                @endif
            </div>
        @endif
    </div>
</section>
