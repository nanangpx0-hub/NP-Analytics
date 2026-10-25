<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="Nanang Pamungkas">
    <meta name="description" content="NP Analytics - Aplikasi Statistik Daerah Offline">
    <title>{{ $title ?? 'NP Analytics' }} - Statistik Daerah</title>

    {{-- Vite (Local Tailwind Build) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire Styles --}}
    @livewireStyles

    @stack('styles')
</head>
<body class="bg-brand-bg text-slate-900 antialiased">
    {{-- Livewire Progress Bar --}}
    <div wire:loading class="livewire-progress-top fixed inset-x-0 z-[60] h-0.5 bg-brand-gold/40 after:absolute after:h-full after:w-1/3 after:bg-brand-gold after:animate-[slide_1s_ease-in-out_infinite]"
         style="pointer-events:none;"></div>

    {{-- Top Bar --}}
    <nav aria-label="Navigasi utama" class="safe-area-top bg-brand-navy shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 md:h-16">
                {{-- Brand --}}
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 min-w-0">
                    <svg class="w-7 h-7 md:w-8 md:h-8 text-brand-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="leading-tight min-w-0">
                        <span class="block text-white font-bold text-base md:text-lg truncate">NP Analytics</span>
                        <span class="hidden sm:block text-brand-gold text-xs">Statistik Daerah</span>
                    </span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('dashboard') ? 'bg-brand-gold text-brand-navy' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('indicators.index') }}" wire:navigate
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('indicators.*') ? 'bg-brand-gold text-brand-navy' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Indikator
                    </a>
                </div>

                {{-- Sync Button (dipakai semua ukuran layar) --}}
                <livewire:sync-button />
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="app-main-bottom-safe max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        {{ $slot }}
    </main>

    {{-- Footer (desktop saja) --}}
    <footer class="hidden md:block bg-brand-navy text-slate-400">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm">
            <p>&copy; {{ date('Y') }} <span class="text-brand-gold font-semibold">Nanang Pamungkas</span></p>
            <p class="text-xs">Laravel + Livewire &bull; SQLite offline &bull; NativePHP (Android)</p>
        </div>
    </footer>

    {{-- Bottom Tab Bar (mobile) --}}
    <nav aria-label="Navigasi bawah" class="safe-area-bottom md:hidden fixed bottom-0 inset-x-0 z-50 bg-brand-navy shadow-[0_-4px_20px_rgba(0,0,0,0.25)] border-t border-white/10">
        <div class="flex h-16">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex flex-col items-center justify-center gap-1 flex-1 text-xs font-medium {{ request()->routeIs('dashboard') ? 'text-brand-gold' : 'text-slate-400 active:bg-white/5' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Beranda
            </a>
            <a href="{{ route('indicators.index') }}" wire:navigate
               class="flex flex-col items-center justify-center gap-1 flex-1 text-xs font-medium {{ request()->routeIs('indicators.*') ? 'text-brand-gold' : 'text-slate-400 active:bg-white/5' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                Indikator
            </a>
        </div>
    </nav>

    @livewireScripts

    @stack('scripts')
</body>
</html>
