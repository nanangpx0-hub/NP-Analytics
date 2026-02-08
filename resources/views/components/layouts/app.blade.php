<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Nanang Pamungkas">
    <meta name="description" content="NP Analytics - Aplikasi Statistik Daerah Offline">
    <title>{{ $title ?? 'NP Analytics' }} - Statistik Daerah</title>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire Styles --}}
    @livewireStyles
    
    {{-- Additional Styles --}}
    @stack('styles')
</head>
<body class="bg-brand-bg text-gray-900 antialiased">
    {{-- Navigation --}}
    <nav class="bg-brand-navy shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo & Brand --}}
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg">NP Analytics</h1>
                        <p class="text-brand-gold text-xs">Statistik Daerah</p>
                    </div>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('dashboard') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('dashboard') ? 'bg-brand-gold text-brand-navy' : 'text-gray-300 hover:bg-brand-navy/50 hover:text-white' }}">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </div>
                    </a>
                    
                    <a href="{{ route('indicators.index') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('indicators.*') ? 'bg-brand-gold text-brand-navy' : 'text-gray-300 hover:bg-brand-navy/50 hover:text-white' }}">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            Indikator
                        </div>
                    </a>
                </div>

                {{-- Mobile Menu Button --}}
                <div class="md:hidden">
                    <button id="mobile-menu-button" 
                            class="text-gray-300 hover:text-white p-2 rounded-lg hover:bg-brand-navy/50 transition-colors"
                            aria-label="Toggle menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Navigation --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="block px-3 py-2 rounded-lg text-base font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-brand-gold text-brand-navy' : 'text-gray-300 hover:bg-brand-navy/50 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('indicators.index') }}" 
                   class="block px-3 py-2 rounded-lg text-base font-medium transition-colors
                          {{ request()->routeIs('indicators.*') ? 'bg-brand-gold text-brand-navy' : 'text-gray-300 hover:bg-brand-navy/50 hover:text-white' }}">
                    Indikator
                </a>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <livewire:sync-button />
        </div>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-brand-navy text-gray-300 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- About --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        NP Analytics
                    </h3>
                    <p class="text-sm leading-relaxed">
                        Aplikasi statistik daerah offline-first yang dibangun dengan Laravel & NativePHP untuk memudahkan analisis data statistik.
                    </p>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-3">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="{{ route('dashboard') }}" class="hover:text-brand-gold transition-colors">Dashboard</a>
                        </li>
                        <li>
                            <a href="{{ route('indicators.index') }}" class="hover:text-brand-gold transition-colors">Daftar Indikator</a>
                        </li>
                    </ul>
                </div>

                {{-- Info --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-3">Informasi</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            <span>Laravel 10 + Livewire 3</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <span>SQLite (Offline)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span>NativePHP (Android)</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-sm">
                <p>&copy; {{ date('Y') }} <span class="text-brand-gold font-semibold">Nanang Pamungkas</span>. All rights reserved.</p>
                <p class="mt-1 text-gray-400">Built with ❤️ using Laravel & NativePHP</p>
            </div>
        </div>
    </footer>

    {{-- Livewire Scripts --}}
    @livewireScripts
    
    {{-- Mobile Menu Toggle Script --}}
    <script>
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    
    {{-- Additional Scripts --}}
    @stack('scripts')
</body>
</html>
