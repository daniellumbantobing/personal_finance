<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Personal Finance') }} - Autentikasi</title>

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

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                            display: ['Plus Jakarta Sans', 'sans-serif'],
                        },
                        borderRadius: {
                            "3xl": "1.75rem",
                        },
                        boxShadow: {
                            'card': '0 10px 30px -5px rgba(0,0,0,.04),0 4px 12px -2px rgba(0,0,0,.02)',
                        }
                    }
                }
            }
        </script>

        <!-- Livewire Styles -->
        @livewireStyles

        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                display: inline-block;
                vertical-align: middle;
            }
            .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
            .shadow-card { box-shadow: 0 10px 30px -5px rgba(0,0,0,.04),0 4px 12px -2px rgba(0,0,0,.02); }
        </style>
    </head>
    <body class="bg-[#f8faff] dark:bg-slate-950 font-sans antialiased selection:bg-indigo-100 selection:text-indigo-900">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute -right-20 -top-20 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 text-center mb-8">
                <a href="/" wire:navigate class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gradient-to-tr from-slate-900 via-indigo-950 to-indigo-600 text-white shadow-xl shadow-indigo-500/20 mb-4 transition-transform hover:scale-105">
                    <span class="material-symbols-outlined text-[32px]">all_inclusive</span>
                </a>
                <h1 class="font-display font-extrabold text-2xl tracking-tight text-slate-900 dark:text-white">Personal Finance</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Sistem Manajemen Keuangan Pribadi</p>
            </div>

            <div class="relative z-10 w-full sm:max-w-md px-8 py-10 bg-white dark:bg-slate-900 shadow-card overflow-hidden sm:rounded-[2rem] border border-slate-100/80 dark:border-slate-800">
                {{ $slot }}
            </div>

            <div class="relative z-10 mt-8 text-xs text-slate-400">
                &copy; {{ date('Y') }} FinAI. All rights reserved.
            </div>
        </div>

        @livewireScripts
    </body>
</html>
