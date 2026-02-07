<div>
        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('indicators.index') }}" 
               class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Daftar Indikator
            </a>
        </div>

        {{-- Main Card --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            {{-- Header --}}
            <div class="bg-brand-navy text-white p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="flex-1">
                        {{-- Category Badge --}}
                        @if($category)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-gold text-brand-navy text-xs font-semibold rounded-full mb-3">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                {{ $category->name }}
                            </span>
                        @endif

                        {{-- Title --}}
                        <h1 class="text-2xl md:text-3xl font-bold mb-2">{{ $indicator->title }}</h1>
                        
                        {{-- Year --}}
                        <p class="text-gray-300">Data Tahun {{ $indicator->year }}</p>
                    </div>

                    {{-- Trend Badge --}}
                    @if($trendData['value'] !== null)
                        <div class="flex-shrink-0">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                                {{ $trendData['is_positive'] ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                @if($trendData['icon'] === 'arrow-up')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                    </svg>
                                @elseif($trendData['icon'] === 'arrow-down')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                    </svg>
                                @endif
                                <span class="text-lg font-bold">{{ $trendData['formatted'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                {{-- Value Display --}}
                <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                    <p class="text-sm text-gray-500 mb-1">Nilai Indikator</p>
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-4xl md:text-5xl font-bold text-brand-navy">
                            {{ number_format($indicator->value, ($indicator->value == intval($indicator->value) ? 0 : 2), ',', '.') }}
                        </span>
                        <span class="text-xl text-gray-600 font-medium">{{ $indicator->unit }}</span>
                    </div>
                </div>

                {{-- Description --}}
                @if($indicator->description)
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-brand-navy mb-3">Deskripsi</h2>
                        <p class="text-gray-700 leading-relaxed">{{ $indicator->description }}</p>
                    </div>
                @endif

                {{-- Image (if exists) --}}
                @if($indicator->image_path)
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-brand-navy mb-3">Visualisasi</h2>
                        <div class="rounded-lg overflow-hidden border border-gray-200">
                            <img src="{{ asset($indicator->image_path) }}" 
                                 alt="Visualisasi {{ $indicator->title }}"
                                 class="w-full h-auto"
                                 loading="lazy">
                        </div>
                    </div>
                @endif

                {{-- Phenomena Section --}}
                @if($phenomenaStats['total'] > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-brand-navy">Fenomena Terkait</h2>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="inline-flex items-center gap-1 text-green-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $phenomenaStats['positive'] }} Positif
                                </span>
                                <span class="inline-flex items-center gap-1 text-red-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $phenomenaStats['negative'] }} Negatif
                                </span>
                                <span class="inline-flex items-center gap-1 text-slate-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 9a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $phenomenaStats['neutral'] }} Netral
                                </span>
                            </div>
                        </div>

                        {{-- Phenomena List --}}
                        <div class="space-y-4">
                            @foreach($phenomena as $phenomenon)
                                @php
                                    $impactStyles = match ($phenomenon->impact) {
                                        'positive' => [
                                            'bg' => 'bg-green-50',
                                            'border' => 'border-green-500',
                                            'text' => 'text-green-600',
                                            'icon' => 'positive',
                                        ],
                                        'negative' => [
                                            'bg' => 'bg-red-50',
                                            'border' => 'border-red-500',
                                            'text' => 'text-red-600',
                                            'icon' => 'negative',
                                        ],
                                        default => [
                                            'bg' => 'bg-slate-50',
                                            'border' => 'border-slate-400',
                                            'text' => 'text-slate-600',
                                            'icon' => 'neutral',
                                        ],
                                    };
                                @endphp
                                <div class="p-4 rounded-lg border-l-4 {{ $impactStyles['bg'] }} {{ $impactStyles['border'] }}">
                                    <div class="flex items-start gap-3">
                                        @if($impactStyles['icon'] === 'positive')
                                            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif($impactStyles['icon'] === 'negative')
                                            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 9a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="font-semibold text-gray-900">{{ $phenomenon->title }}</h3>
                                                <span class="text-xs font-semibold {{ $impactStyles['text'] }}">{{ $phenomenon->impact_label }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 mt-1">{{ $phenomenon->description }}</p>
                                            @if(!empty($phenomenon->source))
                                                <p class="text-xs text-gray-500 mt-1">Sumber: {{ $phenomenon->source }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Show More Button --}}
                        @if($hasMorePhenomena)
                            <div class="mt-4 text-center">
                                <button wire:click="togglePhenomena" 
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-navy bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    @if($showAllPhenomena)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                        Tampilkan Lebih Sedikit
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        Tampilkan Semua ({{ $phenomenaStats['total'] }})
                                    @endif
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="border-t border-gray-200 pt-6">
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p>Belum ada fenomena terkait untuk indikator ini.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
