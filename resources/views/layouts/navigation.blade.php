<header class="sticky top-0 z-50 px-4 sm:px-8 py-3 backdrop-blur-xl bg-white/90 dark:bg-slate-900/90 border-b border-slate-200/80 dark:border-slate-800/80 transition-all shadow-sm">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Brand & Persona Context -->
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-slate-900 via-indigo-950 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[22px] icon-fill">all_inclusive</span>
                </a>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-display font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">Personal Finance</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100/70 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300/40 dark:border-emerald-700/50">FinAI Pro</span>
                    </div>
                </div>
            </div>
            
            <!-- Sleek Floating Nav Pills -->
            <nav class="hidden xl:flex items-center gap-1 bg-slate-100/80 dark:bg-slate-800/80 p-1 rounded-full border border-slate-200 dark:border-slate-700/50 text-xs font-medium">
                <a class="{{ request()->routeIs('dashboard') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('dashboard') }}">Ringkasan</a>
                <a class="{{ request()->routeIs('transactions.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('transactions.index') }}">Aktivitas</a>
                <a class="{{ request()->routeIs('budgets.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('budgets.index') }}">Anggaran</a>
                <a class="{{ request()->routeIs('saving-goals.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('saving-goals.index') }}">Tabungan</a>
                <a class="{{ request()->routeIs('recurring.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('recurring.index') }}">Rutin</a>
                <a class="{{ request()->routeIs('debts.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('debts.index') }}">Hutang</a>
                <a class="{{ request()->routeIs('reports.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('reports.index') }}">Laporan</a>
                <a class="{{ request()->routeIs('accounts.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('accounts.index') }}">Dompet</a>
                <a class="{{ request()->routeIs('categories.*') ? 'px-3 py-1.5 rounded-full bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm' : 'px-3 py-1.5 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors' }}" href="{{ route('categories.index') }}">Kategori</a>
            </nav>
        </div>
        
        <!-- Quick Action Controls & User Capsule -->
        <div class="flex items-center gap-3">
            <a href="{{ route('transactions.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-semibold text-xs transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-[16px]">add</span>
                <span class="hidden sm:inline">Tambah</span>
            </a>
            
            <!-- Dark Mode Toggle -->
            <button 
                x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                x-init="$watch('isDark', v => { localStorage.setItem('theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v); })"
                @click="isDark = !isDark"
                class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors mr-1">
                <span x-show="!isDark" class="material-symbols-outlined text-[18px]">dark_mode</span>
                <span x-show="isDark" style="display:none;" class="material-symbols-outlined text-[18px]">light_mode</span>
            </button>
            
            <!-- User Profile Dropdown -->
            <div class="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-700" x-data="{ open: false }">
                <div @click="open = !open" @click.away="open = false" class="relative">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 p-[2px] shadow-sm cursor-pointer hover:scale-105 transition-transform">
                        <div class="w-full h-full rounded-full bg-white dark:bg-slate-800 flex items-center justify-center font-display font-bold text-xs text-indigo-700 dark:text-indigo-300">
                            {{ substr(auth()->user()->name ?? 'U', 0, 2) }}
                        </div>
                    </div>
                    
                    <div x-show="open" style="display: none;" class="absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-float py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50">Profil</a>
                        <a href="{{ route('debts.index') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50">Hutang &amp; Piutang</a>
                        <a href="{{ route('accounts.index') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50">Kelola Dompet</a>
                        <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50">Kelola Kategori</a>
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 font-medium">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <div class="flex xl:hidden ml-2" x-data="{ open: false }">
                <button @click="open = !open" class="text-slate-600 dark:text-slate-400 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <!-- Mobile Navigation Menu -->
                <div x-show="open" @click.away="open = false" style="display: none;" class="absolute top-16 left-0 right-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-float p-4 z-40 space-y-1">
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Ringkasan</a>
                    <a href="{{ route('transactions.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Aktivitas</a>
                    <a href="{{ route('budgets.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('budgets.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Anggaran</a>
                    <a href="{{ route('saving-goals.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('saving-goals.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Tabungan</a>
                    <a href="{{ route('recurring.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('recurring.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Rutin</a>
                    <a href="{{ route('debts.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('debts.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Hutang &amp; Piutang</a>
                    <a href="{{ route('reports.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Laporan</a>
                    <a href="{{ route('accounts.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('accounts.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Dompet</a>
                    <a href="{{ route('categories.index') }}" class="block px-4 py-2.5 text-sm font-medium {{ request()->routeIs('categories.*') ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} rounded-xl">Kategori</a>
                    <div class="border-t border-slate-100 dark:border-slate-700 pt-2 mt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-rose-600 dark:text-rose-400 font-medium">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

