<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Personal Finance') }}</title>

        <!-- Favicon / Tab Icons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#4f46e5">

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

        <!-- Material Symbols -->
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS CDN (Play CDN - no build needed) -->
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            "surface": "#f8f9ff",
                            "on-surface": "#0b1c30",
                            "on-surface-variant": "#45464d",
                            "primary": "#0f172a",
                            "secondary": "#006c49",
                            "error": "#e11d48",
                        },
                        fontFamily: {
                            sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                            display: ['Plus Jakarta Sans', 'sans-serif'],
                        },
                        borderRadius: {
                            "2xl": "1.25rem",
                            "3xl": "1.75rem",
                            "4xl": "2.25rem"
                        },
                        boxShadow: {
                            'soft': '0 4px 25px -4px rgba(15,23,42,.05),0 2px 10px -2px rgba(15,23,42,.03)',
                            'card': '0 10px 30px -5px rgba(0,0,0,.04),0 4px 12px -2px rgba(0,0,0,.02)',
                            'glow': '0 0 40px -10px rgba(99,102,241,.25)',
                            'float': '0 20px 40px -15px rgba(15,23,42,.12)',
                        }
                    }
                }
            }
        </script>

        <!-- Livewire Styles -->
        @livewireStyles

        <!-- Dark Mode: Apply IMMEDIATELY before any render to prevent flash -->
        <script>
            (function() {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Re-apply dark class after every Livewire navigate -->
        <script>
            document.addEventListener('livewire:navigated', function() {
                var theme = localStorage.getItem('theme');
                document.documentElement.classList.toggle('dark',
                    theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)
                );
            });
        </script>

        <!-- Global Currency Formatter & Cleaner (Pemisah Ribuan) -->
        <script>
            window.cleanNominal = function(val) {
                if (val === undefined || val === null || val === '') return '';
                if (typeof val === 'number') {
                    return Math.round(val).toString();
                }
                let str = val.toString().trim();
                // If it ends with decimal cents like .00 or .50 (e.g. from DB "10000.00")
                if (/^\-?\d+\.\d{1,2}$/.test(str)) {
                    return Math.round(parseFloat(str)).toString();
                }
                // Strip all non-digits except leading minus
                let isNeg = str.startsWith('-');
                let digits = str.replace(/[^0-9]/g, '');
                return digits ? (isNeg ? '-' + digits : digits) : '';
            };

            window.formatRupiah = function(val) {
                let clean = window.cleanNominal(val);
                if (!clean) return '';
                let isNeg = clean.startsWith('-');
                let num = Math.abs(parseInt(clean, 10));
                let formatted = new Intl.NumberFormat('id-ID').format(num);
                return isNeg ? '-' + formatted : formatted;
            };
        </script>

        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                display: inline-block;
                vertical-align: middle;
                line-height: 1;
            }
            .icon-fill {
                font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
            .no-scrollbar::-webkit-scrollbar { display: none; }
            .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
            /* Custom shadow utilities not in CDN */
            .shadow-card { box-shadow: 0 10px 30px -5px rgba(0,0,0,.04),0 4px 12px -2px rgba(0,0,0,.02); }
            .shadow-soft { box-shadow: 0 4px 25px -4px rgba(15,23,42,.05),0 2px 10px -2px rgba(15,23,42,.03); }
            .shadow-float { box-shadow: 0 20px 40px -15px rgba(15,23,42,.12); }
            .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>
    </head>
    <body class="bg-[#f8faff] dark:bg-slate-950 text-[#0b1c30] dark:text-slate-200 font-sans antialiased min-h-screen selection:bg-indigo-100 selection:text-indigo-900 pb-24">
        <div class="min-h-screen bg-[#f8faff] dark:bg-slate-950">
            @include('layouts.navigation')

            <!-- Global Toast / Flash Notifications -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)" class="max-w-7xl mx-auto px-4 sm:px-8 pt-4">
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-sm font-medium shadow-sm transition-all animate-in fade-in slide-in-from-top-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[20px] text-emerald-600 dark:text-emerald-400 icon-fill">check_circle</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error') || (isset($errors) && $errors->any()))
                <div x-data="{ show: true }" x-show="show" class="max-w-7xl mx-auto px-4 sm:px-8 pt-4">
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-sm font-medium shadow-sm transition-all animate-in fade-in slide-in-from-top-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[20px] text-rose-600 dark:text-rose-400 icon-fill">error</span>
                            <span>{{ session('error') ?? (isset($errors) ? $errors->first() : '') }}</span>
                        </div>
                        <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-8 pt-6 space-y-8">
                {{ $slot }}
            </main>
        </div>

        <!-- Livewire Scripts (includes Alpine when not using Vite) -->
        @livewireScripts
    </body>
</html>
