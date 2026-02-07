@props([
    'indicator',
    'size' => 'md',
    'clickable' => true,
])

@php
    // ========================================
    // SMART TREND LOGIC
    // ========================================
    // Menentukan warna dan icon berdasarkan:
    // 1. is_higher_better (true/false)
    // 2. Arah trend (naik/turun)
    
    $trendDirection = null;
    $trendColor = 'text-gray-600';
    $trendBgColor = 'bg-gray-100';
    $trendBorderColor = 'border-gray-300';
    $trendIcon = null;
    
    if (isset($indicator->trend) && $indicator->trend !== null && $indicator->trend != 0) {
        $isUpTrend = $indicator->trend > 0;
        
        // Smart Trend Logic Implementation
        if ($indicator->is_higher_better) {
            // Higher is better (PDRB, Produksi Padi, IPM)
            if ($isUpTrend) {
                // Naik = BAGUS (Hijau)
                $trendColor = 'text-green-700';
                $trendBgColor = 'bg-green-50';
                $trendBorderColor = 'border-green-300';
                $trendIcon = 'up';
                $trendDirection = 'positive';
            } else {
                // Turun = BURUK (Merah)
                $trendColor = 'text-red-700';
                $trendBgColor = 'bg-red-50';
                $trendBorderColor = 'border-red-300';
                $trendIcon = 'down';
                $trendDirection = 'negative';
            }
        } else {
            // Lower is better (Kemiskinan, Inflasi, Pengangguran)
            if ($isUpTrend) {
                // Naik = BURUK (Merah)
                $trendColor = 'text-red-700';
                $trendBgColor = 'bg-red-50';
                $trendBorderColor = 'border-red-300';
                $trendIcon = 'up';
                $trendDirection = 'negative';
            } else {
                // Turun = BAGUS (Hijau)
                $trendColor = 'text-green-700';
                $trendBgColor = 'bg-green-50';
                $trendBorderColor = 'border-green-300';
                $trendIcon = 'down';
                $trendDirection = 'positive';
            }
        }
    }
    
    // Size variants
    $cardPadding = $size === 'sm' ? 'p-4' : 'p-5';
    $titleSize = $size === 'sm' ? 'text-base' : 'text-lg';
    $valueSize = $size === 'sm' ? 'text-2xl md:text-3xl' : 'text-3xl md:text-4xl';
    
    // Clickable variant
    $clickableClass = $clickable ? 'cursor-pointer hover:shadow-xl hover:-translate-y-1' : '';
    
    // Link to detail page
    $detailUrl = $clickable ? route('indicators.show', $indicator->id) : '#';
@endphp

<a 
    href="{{ $detailUrl }}" 
    {{ $attributes->merge(['class' => "block bg-brand-surface rounded-xl shadow-md transition-all duration-300 overflow-hidden border border-gray-100 {$clickableClass}"]) }}
    @if(!$clickable) onclick="return false;" @endif
>
    {{-- Header Section with Brand Navy --}}
    <div class="bg-brand-navy text-white px-5 py-4">
        <div class="flex items-start justify-between gap-3">
            {{-- Title Area --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2.5 mb-1.5">
                    {{-- Category Badge --}}
                    @if(isset($indicator->category))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-brand-gold/20 text-brand-gold text-xs font-bold rounded-full backdrop-blur-sm">
                            @if($indicator->category->icon)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            @endif
                            {{ $indicator->category->name }}
                        </span>
                    @endif
                </div>
                
                {{-- Title --}}
                <h3 class="font-bold {{ $titleSize }} leading-snug line-clamp-2 text-white">
                    {{ $indicator->title }}
                </h3>
            </div>
            
            {{-- Year Badge with Gold Accent --}}
            <div class="flex-shrink-0">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-brand-gold text-brand-navy text-sm font-bold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ $indicator->year }}
                </span>
            </div>
        </div>
    </div>
    
    {{-- Body Section --}}
    <div class="{{ $cardPadding }}">
        {{-- Value Display --}}
        <div class="mb-4">
            <div class="flex items-baseline gap-2 flex-wrap">
                <p class="font-extrabold {{ $valueSize }} text-brand-navy leading-none tracking-tight">
                    {{ number_format($indicator->value, ($indicator->value == intval($indicator->value) ? 0 : 2), ',', '.') }}
                </p>
                <p class="text-base text-gray-600 font-semibold">
                    {{ $indicator->unit }}
                </p>
            </div>
        </div>
        
        {{-- Trend Indicator with Smart Logic Colors --}}
        @if($trendIcon)
            <div class="mb-4">
                <div class="inline-flex items-center gap-2 px-3 py-2 {{ $trendBgColor }} border {{ $trendBorderColor }} rounded-lg">
                    {{-- Arrow Icon --}}
                    @if($trendIcon === 'up')
                        <svg class="w-5 h-5 {{ $trendColor }}" 
                             fill="currentColor" 
                             viewBox="0 0 20 20"
                             aria-label="Trend naik {{ $trendDirection === 'positive' ? '(bagus)' : '(buruk)' }}">
                            <path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 01.919-.53l4.78 1.281a.75.75 0 01.531.919l-1.281 4.78a.75.75 0 01-1.449-.387l.81-3.022a19.407 19.407 0 00-5.594 5.203.75.75 0 01-1.139.093L7 10.06l-4.72 4.72a.75.75 0 01-1.06-1.061l5.25-5.25a.75.75 0 011.06 0l3.074 3.073a20.923 20.923 0 015.545-4.931l-3.042-.815a.75.75 0 01-.53-.919z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="w-5 h-5 {{ $trendColor }}" 
                             fill="currentColor" 
                             viewBox="0 0 20 20"
                             aria-label="Trend turun {{ $trendDirection === 'positive' ? '(bagus)' : '(buruk)' }}">
                            <path fill-rule="evenodd" d="M12.577 15.122a.75.75 0 01.919.53l.81 3.022a20.923 20.923 0 005.545-4.931l-3.042.815a.75.75 0 11-.388-1.45l4.78-1.28a.75.75 0 01.919.53l1.281 4.78a.75.75 0 01-1.449.388l-.81-3.023a19.407 19.407 0 00-5.594 5.203.75.75 0 01-1.139-.093L7 13.44l-4.72 4.72a.75.75 0 11-1.06-1.061l5.25-5.25a.75.75 0 011.06 0l3.074 3.073a20.923 20.923 0 015.545-4.931l-3.042.815a.75.75 0 01-.53-.919z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    
                    {{-- Percentage --}}
                    <span class="text-sm font-bold {{ $trendColor }}">
                        {{ $indicator->trend > 0 ? '+' : '' }}{{ number_format($indicator->trend, 2, ',', '.') }}%
                    </span>
                    
                    {{-- Status Label --}}
                    <span class="text-xs font-medium {{ $trendColor }} opacity-75">
                        {{ $trendDirection === 'positive' ? '(Bagus)' : '(Perlu Perhatian)' }}
                    </span>
                </div>
            </div>
        @endif
        
        {{-- Description --}}
        @if(!empty($indicator->description))
            <p class="text-sm text-gray-700 leading-relaxed line-clamp-3 mb-3">
                {{ $indicator->description }}
            </p>
        @endif
        
        {{-- Image Thumbnail --}}
        @if(!empty($indicator->image_path))
            <div class="mt-4">
                <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                    <img 
                        src="{{ asset($indicator->image_path) }}" 
                        alt="Visualisasi {{ $indicator->title }}"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
        @endif
        
        {{-- View Details Link (only if clickable) --}}
        @if($clickable)
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Lihat Detail Analisis</span>
                    <svg class="w-5 h-5 text-brand-gold transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </div>
            </div>
        @endif
    </div>
</a>
