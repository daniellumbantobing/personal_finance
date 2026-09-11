<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    public $totalBalance = 0;
    public $monthlyIncome = 0;
    public $monthlyExpense = 0;
    public $netSavings = 0;
    public $expenseRatio = 0;
    public $savingsRate = 0;
    public $financialScore = 50;
    public $scoreGrade = 'Netral';
    public $momGrowth = 0;
    public $currency = 'IDR';
    public $currentMonthName = '';

    public $accounts = [];
    public $accountsCount = 0;

    public $recentTransactions = [];
    public $totalTransactionsCount = 0;
    public $incomeTransactionsCount = 0;
    public $expenseTransactionsCount = 0;

    public $topCategories = [];
    public $topCategoriesTotal = 0;
    public $topCategoriesPct = 0;

    public $activeBudget = null;
    public $activeGoal = null;
    public $dueRecurring = null;
    public $dueDebt = null;

    public $sixMonthsTrend = [];
    public $avgMonthlySurplus = 0;

    public function mount()
    {
        $user = Auth::user();
        $this->currency = $user->currency ?? 'IDR';
        $this->currentMonthName = Carbon::now()->translatedFormat('F');

        // 1. Accounts & Total Balance
        $this->accounts = $user->accounts()->orderBy('name')->get();
        $this->accountsCount = $this->accounts->count();
        $this->totalBalance = (float) $this->accounts->sum('balance');

        // 2. Current Month Income & Expense
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $this->monthlyIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $this->monthlyExpense = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $this->netSavings = $this->monthlyIncome - $this->monthlyExpense;
        $this->expenseRatio = $this->monthlyIncome > 0
            ? min(100, round(($this->monthlyExpense / $this->monthlyIncome) * 100, 1))
            : ($this->monthlyExpense > 0 ? 100 : 0);

        // 3. Real Financial Score & Savings Rate
        $this->savingsRate = $this->monthlyIncome > 0
            ? max(0, round(($this->netSavings / $this->monthlyIncome) * 100))
            : 0;

        if ($this->monthlyIncome > 0) {
            if ($this->savingsRate >= 30) {
                $this->financialScore = 92;
                $this->scoreGrade = 'Level A • Sangat Sehat';
            } elseif ($this->savingsRate >= 20) {
                $this->financialScore = 82;
                $this->scoreGrade = 'Level A • Sehat';
            } elseif ($this->savingsRate >= 10) {
                $this->financialScore = 68;
                $this->scoreGrade = 'Level B • Cukup';
            } else {
                $this->financialScore = 55;
                $this->scoreGrade = 'Level C • Waspada';
            }
        } elseif ($this->monthlyExpense > 0) {
            $this->financialScore = 40;
            $this->scoreGrade = 'Level D • Defisit';
        } else {
            $this->financialScore = 50;
            $this->scoreGrade = 'Netral • Baru Mulai';
        }

        // 4. MoM Growth (Month-over-Month)
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        $lastMonthIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $lastMonthExpense = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $lastMonthNet = $lastMonthIncome - $lastMonthExpense;

        if ($lastMonthNet != 0) {
            $this->momGrowth = round((($this->netSavings - $lastMonthNet) / abs($lastMonthNet)) * 100, 1);
        } else {
            $this->momGrowth = $this->netSavings > 0 ? 100 : 0;
        }

        // 5. Recent Transactions & Counts
        $this->recentTransactions = $user->transactions()
            ->with(['account', 'category'])
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();

        $monthTxs = $user->transactions()->whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);
        $this->totalTransactionsCount = (clone $monthTxs)->count();
        $this->incomeTransactionsCount = (clone $monthTxs)->where('type', 'income')->count();
        $this->expenseTransactionsCount = (clone $monthTxs)->where('type', 'expense')->count();

        // 6. Top 4 Categories
        $this->topCategories = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.icon, sum(transactions.amount) as total')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startOfMonth, $endOfMonth])
            ->groupBy('categories.id', 'categories.name', 'categories.icon')
            ->orderByDesc('total')
            ->take(4)
            ->get();

        $this->topCategoriesTotal = $this->topCategories->sum('total');
        $this->topCategoriesPct = $this->monthlyExpense > 0
            ? round(($this->topCategoriesTotal / $this->monthlyExpense) * 100)
            : 0;

        // 7. Phase 2: Live Budget, Goal, and Recurring
        $this->activeBudget = $user->budgets()->with('category')->first();
        $this->activeGoal = $user->savingGoals()->where('status', 'active')->first();
        $this->dueRecurring = $user->recurringTransactions()
            ->where('is_active', true)
            ->where('next_run_date', '<=', Carbon::now()->addDays(7))
            ->first();

        $this->dueDebt = $user->debts()
            ->where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date')
            ->first();

        // 8. 6-Month Cashflow Real Trend Data
        $this->sixMonthsTrend = [];
        $maxVal = 1;
        for ($i = 5; $i >= 0; $i--) {
            $mStart = Carbon::now()->subMonths($i)->startOfMonth();
            $mEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $in = (float) $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');
            $out = (float) $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            if ($in > $maxVal) $maxVal = $in;
            if ($out > $maxVal) $maxVal = $out;

            $this->sixMonthsTrend[] = [
                'month' => $mStart->translatedFormat('M'),
                'income' => $in,
                'expense' => $out,
                'is_current' => $i === 0,
            ];
        }

        foreach ($this->sixMonthsTrend as &$item) {
            $item['in_pct'] = min(100, max(8, round(($item['income'] / $maxVal) * 100)));
            $item['out_pct'] = min(100, max(8, round(($item['expense'] / $maxVal) * 100)));
        }
        unset($item);

        $totalSurplus = array_sum(array_map(fn($t) => $t['income'] - $t['expense'], $this->sixMonthsTrend));
        $this->avgMonthlySurplus = round($totalSurplus / 6);
    }
}; ?>
<div class="space-y-8 sm:space-y-10 pb-6">

<!-- 1. PERSONAL WARM GREETING & STATUS BANNER -->
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
<div>
<div class="flex flex-wrap items-center gap-2 mb-3">
<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50/80 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/60 text-indigo-700 dark:text-indigo-300 text-xs font-medium">
<span class="material-symbols-outlined text-[15px] icon-fill">auto_awesome</span>
<span>FinAI Intelligence aktif mengawasi saldo &amp; target Anda</span>
</div>
@if($dueRecurring)
<a href="{{ route('recurring.index') }}" wire:navigate.hover class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-400 text-xs font-semibold hover:bg-amber-100 transition-colors">
<span class="material-symbols-outlined text-[14px]">alarm</span>
<span>Tagihan dekat: {{ $dueRecurring->description }} (Rp {{ number_format($dueRecurring->amount, 0, ',', '.') }})</span>
</a>
@endif
@if($dueDebt)
<a href="{{ route('debts.index') }}" wire:navigate.hover class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $dueDebt->is_overdue ? 'bg-rose-50 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-400' : 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-400' }} border text-xs font-semibold hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined text-[14px]">{{ $dueDebt->is_overdue ? 'warning' : 'handshake' }}</span>
<span>{{ $dueDebt->type === 'payable' ? 'Tempo Hutang: ' : 'Tempo Piutang: ' }}{{ $dueDebt->person_name }} (Rp {{ number_format($dueDebt->remaining_amount, 0, ',', '.') }})</span>
</a>
@endif
</div>
<h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
          Selamat Datang, {{ auth()->user()->name }} <span class="text-2xl">✨</span>
</h1>
@if($netSavings >= 0)
<p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Saldo bersih Anda bertumbuh surplus <strong class="text-emerald-600 dark:text-emerald-400 font-semibold">+Rp {{ number_format($netSavings, 0, ',', '.') }}</strong> bulan ini ({{ $savingsRate }}% tabungan).</p>
@else
<p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Pengeluaran melebihi pemasukan bulan ini (defisit <strong class="text-rose-600 dark:text-rose-400 font-semibold">Rp {{ number_format(abs($netSavings), 0, ',', '.') }}</strong>).</p>
@endif
</div>
<!-- Quick Action Pill -->
<div class="flex items-center gap-2">
<span class="px-3.5 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 shadow-sm flex items-center gap-1.5">
<span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          Periode: {{ $currentMonthName }} {{ \Carbon\Carbon::now()->year }}
</span>
<a href="{{ route('dashboard') }}" wire:navigate.hover class="p-2 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:text-white shadow-sm text-xs" title="Refresh data">
<span class="material-symbols-outlined text-[16px]">sync</span>
</a>
</div>
</div>

<!-- 2. HERO SHOWCASE: LUXURY WEALTH CARD & FINANCIAL VITALS -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
<!-- Primary Luxury Wealth Card -->
<div class="lg:col-span-7 rounded-3xl p-7 sm:p-8 relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white shadow-float flex flex-col justify-between border border-slate-800">
<div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
<div class="absolute -left-16 -bottom-16 w-60 h-60 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

<!-- Card Top Bar -->
<div class="relative z-10 flex items-center justify-between">
<div class="flex items-center gap-2.5">
<span class="w-8 h-8 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white border border-white/10">
<span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
</span>
<span class="text-xs uppercase tracking-widest font-semibold text-slate-400">Total Kekayaan Bersih</span>
</div>
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full {{ $momGrowth >= 0 ? 'bg-emerald-500/10 border-emerald-400/20 text-emerald-400' : 'bg-rose-500/10 border-rose-400/20 text-rose-400' }} border text-xs font-semibold">
<span class="material-symbols-outlined text-[14px]">{{ $momGrowth >= 0 ? 'trending_up' : 'trending_down' }}</span>
{{ $momGrowth >= 0 ? '+' : '' }}{{ $momGrowth }}% MoM
</span>
</div>

<!-- Net Worth Big Figure -->
<div class="relative z-10 my-8">
<div class="text-xs text-slate-400 mb-1 font-medium">Konsolidasi {{ $accountsCount }} Dompet &amp; Rekening</div>
<div class="flex items-baseline gap-2">
<span class="text-2xl text-slate-400 font-light font-sans">Rp</span>
<span class="font-display text-4xl sm:text-5xl font-black tracking-tight text-white">{{ number_format($totalBalance, 0, ',', '.') }}</span>
</div>
<p class="text-xs text-slate-400 mt-2 flex items-center gap-2">
<span class="inline-block w-1.5 h-1.5 rounded-full {{ $netSavings >= 0 ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
@if($netSavings >= 0)
Surplus bulan ini mencapai <strong class="text-emerald-300 font-semibold">+Rp {{ number_format($netSavings, 0, ',', '.') }}</strong>
@else
Defisit kas bulan ini <strong class="text-rose-300 font-semibold">-Rp {{ number_format(abs($netSavings), 0, ',', '.') }}</strong>
@endif
</p>
</div>

<!-- Bottom Micro Stats -->
<div class="relative z-10 pt-5 border-t border-white/10 grid grid-cols-2 gap-4">
<div>
<span class="text-[11px] text-slate-400 block uppercase font-medium">Pemasukan ({{ $currentMonthName }})</span>
<span class="font-display text-base font-bold text-emerald-400">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</span>
<span class="text-[10px] text-slate-400 block mt-0.5">{{ $incomeTransactionsCount }} Transaksi masuk</span>
</div>
<div class="border-l border-white/10 pl-4">
<span class="text-[11px] text-slate-400 block uppercase font-medium">Pengeluaran Terpakai</span>
<div class="flex items-center gap-2">
<span class="font-display text-base font-bold text-rose-400">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
<span class="text-[10px] px-1.5 py-0.5 rounded bg-white/10 text-slate-300 font-mono">{{ $expenseRatio }}%</span>
</div>
<span class="text-[10px] text-slate-400 block mt-0.5">{{ $expenseTransactionsCount }} Transaksi keluar</span>
</div>
</div>
</div>

<!-- Right: Health Vital & Target Tabungan -->
<div class="lg:col-span-5 flex flex-col gap-6">
<!-- Score & Vital Card -->
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between flex-1">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2.5">
<div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">verified</span>
</div>
<span class="font-display font-bold text-sm text-slate-800 dark:text-slate-200">Kesehatan Finansial</span>
</div>
<span class="px-3 py-1 rounded-full text-xs font-bold {{ $financialScore >= 75 ? 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($financialScore >= 50 ? 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 border border-indigo-200' : 'bg-rose-100 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-200') }}">{{ $scoreGrade }}</span>
</div>
<div class="flex items-center gap-6 my-2">
<!-- Progress Dial -->
<div class="relative w-20 h-20 flex-shrink-0">
<svg class="w-20 h-20 transform -rotate-90" viewbox="0 0 36 36">
<path class="text-slate-100 dark:text-slate-800" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4"></path>
<path class="{{ $financialScore >= 75 ? 'text-emerald-500' : ($financialScore >= 50 ? 'text-indigo-500' : 'text-rose-500') }}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ $financialScore }}, 100" stroke-linecap="round" stroke-width="4"></path>
</svg>
<div class="absolute inset-0 flex flex-col items-center justify-center text-center">
<span class="font-display text-xl font-extrabold text-slate-900 dark:text-white">{{ $financialScore }}</span>
<span class="text-[9px] text-slate-400 uppercase font-semibold">Skor</span>
</div>
</div>
<div class="space-y-1">
<p class="text-sm text-slate-800 dark:text-slate-200 font-semibold leading-snug">Rasio tabungan di angka {{ $savingsRate }}%</p>
<p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
{{ $savingsRate >= 20 ? 'Target saving rate minimal 20% terpenuhi secara optimal bulan ini.' : 'Tingkatkan simpanan kas agar skor finansial mencapai level prima.' }}
</p>
</div>
</div>
<div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
<span class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
<span class="material-symbols-outlined text-[15px] text-emerald-600 dark:text-emerald-400">shield</span>
Berdasarkan data riil akun
</span>
<a href="{{ route('reports.index') }}" wire:navigate.hover class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Lihat Laporan →</a>
</div>
</div>

<!-- Target Tabungan Compact Card -->
@if($activeGoal)
<div class="bg-gradient-to-r from-emerald-50/60 to-teal-50/40 dark:from-emerald-950/30 dark:to-teal-950/20 rounded-3xl p-5 sm:p-6 border border-emerald-200/50 dark:border-emerald-800/40 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div class="space-y-1.5 flex-1 min-w-0">
<div class="flex items-center gap-2">
<span class="text-base">🎯</span>
<span class="font-display font-bold text-xs text-slate-900 dark:text-white truncate">{{ $activeGoal->name }}</span>
<span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-200/60 dark:bg-emerald-900/50 text-emerald-900 dark:text-emerald-300 font-semibold flex-shrink-0">{{ $activeGoal->percentage }}%</span>
</div>
<p class="text-xs text-slate-600 dark:text-slate-400">
Terkumpul <strong class="font-semibold text-slate-900 dark:text-white">Rp {{ number_format($activeGoal->current_amount, 0, ',', '.') }}</strong> dari target <span class="font-semibold">Rp {{ number_format($activeGoal->target_amount, 0, ',', '.') }}</span>
</p>
<div class="w-full max-w-xs bg-emerald-200/60 dark:bg-emerald-900/40 rounded-full h-1.5 mt-2">
<div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ min(100, $activeGoal->percentage) }}%"></div>
</div>
</div>
<div class="text-left sm:text-right flex-shrink-0">
<span class="text-[10px] text-slate-500 dark:text-slate-400 block">{{ $activeGoal->target_date ? 'Deadline ' . $activeGoal->target_date->translatedFormat('M Y') : 'Tanpa Deadline' }}</span>
<a href="{{ route('saving-goals.index') }}" wire:navigate.hover class="inline-block mt-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm">
Kelola Tabungan
</a>
</div>
</div>
@else
<div class="bg-gradient-to-r from-emerald-50/60 to-teal-50/40 dark:from-emerald-950/30 dark:to-teal-950/20 rounded-3xl p-5 sm:p-6 border border-emerald-200/50 dark:border-emerald-800/40 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div class="space-y-1">
<div class="flex items-center gap-2">
<span class="text-base">🎯</span>
<span class="font-display font-bold text-xs text-slate-900 dark:text-white">Belum Ada Target Tabungan</span>
</div>
<p class="text-xs text-slate-600 dark:text-slate-400">Tentukan target dana darurat atau impian belanja Anda.</p>
</div>
<div class="flex-shrink-0">
<a href="{{ route('saving-goals.index') }}" wire:navigate.hover class="inline-block text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-white dark:bg-slate-900 px-3.5 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm">
Buat Target →
</a>
</div>
</div>
@endif
</div>
</section>

<!-- 3. SMART WALLET & ACCOUNTS CAROUSEL -->
<section class="space-y-4">
<div class="flex items-center justify-between">
<div class="flex items-center gap-2">
<span class="font-display font-bold text-base text-slate-900 dark:text-white">Dompet &amp; Rekening Terhubung</span>
<span class="text-xs text-slate-400 font-medium">({{ $accountsCount }} Akun Aktif)</span>
</div>
<a href="{{ route('accounts.index') }}" wire:navigate.hover class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
<span>+ Kelola Dompet</span>
</a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
@forelse($accounts as $account)
    @php
        $bgClass = match($account->type) {
            'bank' => 'from-[#0057a3] to-[#003b70] text-white',
            'ewallet' => 'from-[#0081a7] to-[#00afb9] text-white',
            'cash' => 'from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 text-slate-800 dark:text-white border border-slate-300/80 dark:border-slate-700',
            'credit_card' => 'from-[#4c1d95] to-[#3b0764] text-white',
            'investment' => 'from-[#0f172a] to-[#1e293b] text-white',
            default => 'from-[#003566] to-[#001d3d] text-white'
        };
        $icon = match($account->type) {
            'bank' => 'account_balance',
            'ewallet' => 'account_balance_wallet',
            'cash' => 'payments',
            'credit_card' => 'credit_card',
            'investment' => 'trending_up',
            default => 'account_balance_wallet'
        };
    @endphp
    <div class="group bg-gradient-to-br {{ $bgClass }} p-5 rounded-3xl shadow-card relative overflow-hidden flex flex-col justify-between h-36 transition-all hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <span class="font-display font-black text-sm tracking-wider uppercase opacity-90">{{ $account->name }}</span>
            <span class="material-symbols-outlined text-[18px] opacity-70">{{ $icon }}</span>
        </div>
        <div>
            <span class="text-[10px] uppercase tracking-wider opacity-80 block capitalize">{{ $account->type }}</span>
            <span class="font-display text-lg font-bold tracking-tight">Rp {{ number_format($account->balance, 0, ',', '.') }}</span>
        </div>
    </div>
@empty
    <div class="col-span-full py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Belum ada dompet/rekening. <a href="{{ route('accounts.index') }}" wire:navigate.hover class="text-indigo-600 dark:text-indigo-400 font-bold underline ml-1">Tambah Dompet</a></div>
@endforelse
</div>
</section>

<!-- 4. INTERACTIVE FINANCIAL ADVISOR FEED & INSIGHT CARDS -->
<section class="rounded-3xl bg-gradient-to-b from-indigo-50/70 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 border border-indigo-100 dark:border-indigo-800/80 p-6 sm:p-8 shadow-card relative overflow-hidden space-y-6">
<div class="absolute top-0 right-0 w-96 h-96 bg-indigo-200/30 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

<!-- AI Advisor Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 relative z-10">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/30">
<span class="material-symbols-outlined text-[20px] icon-fill">neurology</span>
</div>
<div>
<h2 class="font-display font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
              FinAI Advisor Insight
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400">Pemberitahuan Personal</span>
</h2>
<p class="text-xs text-slate-500 dark:text-slate-400">Analisa pintar berbasis data transaksi riil Anda</p>
</div>
</div>
<div class="flex items-center gap-2">
<span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-full border border-indigo-200/60 dark:border-indigo-800/60 shadow-xs">
<span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
            Real-time Telemetry
          </span>
</div>
</div>

<!-- 3 Advisor Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
<!-- Insight 1: Spending Behavior -->
@if($topCategories->isNotEmpty())
@php
    $topCat = $topCategories->first();
    $topPct = $monthlyExpense > 0 ? round(($topCat->total / $monthlyExpense) * 100) : 0;
@endphp
<div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-soft flex flex-col justify-between h-full hover:border-indigo-200 dark:hover:border-slate-700 transition-all group">
<div>
<div class="flex items-center justify-between mb-3">
<span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">{{ $topCat->icon ?? 'category' }}</span>
</span>
<span class="text-[11px] font-semibold text-slate-400">Pola Konsumsi Terbesar</span>
</div>
<h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">{{ $topCat->name }} Mendominasi</h3>
<p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
Pengeluaran kategori ini mencapai <strong class="text-slate-900 dark:text-white">Rp {{ number_format($topCat->total, 0, ',', '.') }}</strong> ({{ $topPct }}% dari total beban kas bulan ini).
</p>
</div>
<div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
<span class="text-slate-400">{{ $topPct }}% dari total belanja</span>
<a class="font-semibold text-indigo-600 dark:text-indigo-400 group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5" href="{{ route('reports.index') }}" wire:navigate.hover>
Audit Laporan →
</a>
</div>
</div>
@else
<div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-soft flex flex-col justify-between h-full">
<div>
<div class="flex items-center justify-between mb-3">
<span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">check_circle</span>
</span>
<span class="text-[11px] font-semibold text-slate-400">Pola Konsumsi</span>
</div>
<h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">Pengeluaran Terkendali</h3>
<p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Belum ada pengeluaran besar yang tercatat pada bulan ini.</p>
</div>
<div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400">Kondisi stabil</div>
</div>
@endif

<!-- Insight 2: Live Budget Status -->
@if($activeBudget)
    @php
        $bSpent = $activeBudget->spent;
        $bPct = $activeBudget->percentage;
        $bRemaining = max(0, $activeBudget->amount - $bSpent);
    @endphp
    <div class="bg-gradient-to-b from-rose-50/50 dark:from-rose-950/30 to-white dark:to-slate-900 rounded-2xl p-5 sm:p-6 border border-rose-200/70 dark:border-rose-800/50 shadow-soft flex flex-col justify-between h-full hover:border-rose-300 transition-all">
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="w-8 h-8 rounded-xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-400">{{ $bPct }}% Terpakai</span>
            </div>
            <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">Anggaran {{ $activeBudget->category->name }}</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Tersisa <strong class="text-rose-600 font-bold">Rp {{ number_format($bRemaining, 0, ',', '.') }}</strong> dari limit Rp {{ number_format($activeBudget->amount, 0, ',', '.') }}.
            </p>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-3">
                <div class="bg-rose-500 h-1.5 rounded-full" style="width: {{ $bPct }}%"></div>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-rose-100/60 dark:border-rose-800/60 flex items-center justify-between text-xs">
            <span class="text-rose-600 font-medium">{{ $bSpent > $activeBudget->amount ? 'Overbudget' : 'Batas aktif' }}</span>
            <a href="{{ route('budgets.index') }}" wire:navigate.hover class="font-semibold text-slate-800 dark:text-slate-200 hover:text-slate-900 dark:text-white bg-white dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 shadow-xs">
                Kelola Anggaran
            </a>
        </div>
    </div>
@else
    <div class="bg-gradient-to-b from-indigo-50/50 dark:from-indigo-950/30 to-white dark:to-slate-900 rounded-2xl p-5 sm:p-6 border border-indigo-200/70 dark:border-indigo-800/50 shadow-soft flex flex-col justify-between h-full hover:border-indigo-300 transition-all">
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400">Rekomendasi</span>
            </div>
            <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">Pasang Batas Anggaran</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Tetapkan batas belanja kategori agar arus kas bulanan Anda tetap terkontrol rapi.
            </p>
        </div>
        <div class="mt-4 pt-3 border-t border-indigo-100/60 dark:border-indigo-800/60 flex items-center justify-between text-xs">
            <span class="text-indigo-600 font-medium">Fitur baru</span>
            <a href="{{ route('budgets.index') }}" wire:navigate.hover class="font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 rounded-lg shadow-xs">
                Buat Anggaran →
            </a>
        </div>
    </div>
@endif

<!-- Insight 3: Saving Goal -->
@if($activeGoal)
    @php
        $gPct = $activeGoal->percentage;
    @endphp
    <div class="bg-gradient-to-b from-emerald-50/50 dark:from-emerald-950/30 to-white dark:to-slate-900 rounded-2xl p-5 sm:p-6 border border-emerald-200/70 dark:border-emerald-800/50 shadow-soft flex flex-col justify-between h-full hover:border-emerald-300 transition-all">
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] icon-fill">flag</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">{{ $gPct }}% Terkumpul</span>
            </div>
            <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">Target: {{ $activeGoal->name }}</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Terkumpul <strong class="text-slate-900 dark:text-white">Rp {{ number_format($activeGoal->current_amount, 0, ',', '.') }}</strong> dari sasaran <strong class="text-emerald-700 dark:text-emerald-400">Rp {{ number_format($activeGoal->target_amount, 0, ',', '.') }}</strong>.
            </p>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-3">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $gPct }}%"></div>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-emerald-100/60 dark:border-emerald-800/60 flex items-center justify-between text-xs">
            <span class="text-emerald-600 font-medium">Impian</span>
            <a href="{{ route('saving-goals.index') }}" wire:navigate.hover class="font-semibold text-emerald-700 dark:text-emerald-300 hover:underline">
                Setor / Detail →
            </a>
        </div>
    </div>
@else
    <div class="bg-gradient-to-b from-emerald-50/50 dark:from-emerald-950/30 to-white dark:to-slate-900 rounded-2xl p-5 sm:p-6 border border-emerald-200/70 dark:border-emerald-800/50 shadow-soft flex flex-col justify-between h-full hover:border-emerald-300 transition-all">
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] icon-fill">flag</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">Target Impian</span>
            </div>
            <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white mb-1">Mulai Target Tabungan</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Tetapkan sasaran seperti Dana Darurat atau Beli Gadget, dan pantau kemajuannya secara otomatis.
            </p>
        </div>
        <div class="mt-4 pt-3 border-t border-emerald-100/60 dark:border-emerald-800/60 flex items-center justify-between text-xs">
            <span class="text-emerald-600 font-medium">Saving Goal</span>
            <a href="{{ route('saving-goals.index') }}" wire:navigate.hover class="font-semibold text-emerald-700 dark:text-emerald-300 hover:underline">
                Buat Target →
            </a>
        </div>
    </div>
@endif
</div>
</section>

<!-- 5. CASHFLOW & EXPENSE BREAKDOWN (REAL 6-MONTH BARS & CATEGORIES) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
<!-- Arus Kas Trends (7 Cols) -->
<div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between">
<div class="flex items-center justify-between mb-6">
<div>
<h2 class="font-display font-bold text-base text-slate-900 dark:text-white">Tren Arus Kas (Inflow vs Outflow)</h2>
<p class="text-xs text-slate-500 dark:text-slate-400">Pergerakan likuiditas 6 bulan terakhir</p>
</div>
<div class="flex items-center gap-3 text-xs">
<span class="flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-400">
<span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Inflow
</span>
<span class="flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-400">
<span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Outflow
</span>
</div>
</div>

<!-- Dynamic 6-Month Bars -->
<div class="pt-2 pb-4">
<div class="grid grid-cols-6 gap-2 sm:gap-4 items-end h-44 px-2">
@foreach($sixMonthsTrend as $mTrend)
<div class="flex flex-col items-center gap-2 h-full justify-end group">
<div class="w-full flex items-end justify-center gap-1.5 h-32 {{ $mTrend['is_current'] ? 'bg-indigo-50/70 dark:bg-indigo-950/40 p-1.5 rounded-2xl border border-indigo-100/50 dark:border-indigo-800/40' : '' }}">
<div class="w-3.5 sm:w-4 {{ $mTrend['is_current'] ? 'bg-emerald-600' : 'bg-emerald-400/80 group-hover:bg-emerald-500' }} rounded-full transition-all" style="height: {{ $mTrend['in_pct'] }}%" title="Pemasukan: Rp {{ number_format($mTrend['income'], 0, ',', '.') }}"></div>
<div class="w-3.5 sm:w-4 {{ $mTrend['is_current'] ? 'bg-rose-500' : 'bg-rose-300/80 group-hover:bg-rose-400' }} rounded-full transition-all" style="height: {{ $mTrend['out_pct'] }}%" title="Pengeluaran: Rp {{ number_format($mTrend['expense'], 0, ',', '.') }}"></div>
</div>
<span class="text-[11px] font-medium {{ $mTrend['is_current'] ? 'text-indigo-700 dark:text-indigo-400 font-bold' : 'text-slate-400' }}">{{ $mTrend['month'] }}</span>
</div>
@endforeach
</div>
</div>

<div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
<span>Surplus Rata-rata: <strong class="text-slate-800 dark:text-white">Rp {{ number_format($avgMonthlySurplus, 0, ',', '.') }} / bln</strong></span>
<span class="{{ $avgMonthlySurplus >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-semibold flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">{{ $avgMonthlySurplus >= 0 ? 'trending_up' : 'trending_down' }}</span>
{{ $avgMonthlySurplus >= 0 ? 'Kondisi kas surplus' : 'Perlu evaluasi defisit' }}
</span>
</div>
</div>

<!-- Kategori Pengeluaran Bulanan (5 Cols) -->
<div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between">
<div class="flex items-center justify-between mb-4">
<div>
<h2 class="font-display font-bold text-base text-slate-900 dark:text-white">Alokasi Pengeluaran</h2>
<p class="text-xs text-slate-500 dark:text-slate-400">Total belanja {{ $currentMonthName }}: Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
</div>
<span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1 rounded-full">Bulan Ini</span>
</div>

<div class="space-y-4 my-2">
@forelse($topCategories as $cat)
    @php
        $pct = $monthlyExpense > 0 ? ($cat->total / $monthlyExpense) * 100 : 0;
        $colorSet = match(rand(1, 4)) {
            1 => ['bg' => 'bg-rose-100 dark:bg-rose-950/40', 'text' => 'text-rose-600 dark:text-rose-400', 'bar' => 'bg-rose-500'],
            2 => ['bg' => 'bg-indigo-100 dark:bg-indigo-950/40', 'text' => 'text-indigo-700 dark:text-indigo-400', 'bar' => 'bg-indigo-600'],
            3 => ['bg' => 'bg-teal-100 dark:bg-teal-950/40', 'text' => 'text-teal-700 dark:text-teal-400', 'bar' => 'bg-teal-600'],
            default => ['bg' => 'bg-amber-100 dark:bg-amber-950/40', 'text' => 'text-amber-700 dark:text-amber-400', 'bar' => 'bg-amber-500']
        };
    @endphp
    <div class="space-y-1.5">
        <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg {{ $colorSet['bg'] }} {{ $colorSet['text'] }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-[14px]">{{ $cat->icon ?? 'label' }}</span>
                </div>
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $cat->name }}</span>
                <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ round($pct) }}%</span>
            </div>
            <span class="font-display font-bold text-slate-900 dark:text-white">Rp {{ number_format($cat->total, 0, ',', '.') }}</span>
        </div>
        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
            <div class="{{ $colorSet['bar'] }} h-2 rounded-full" style="width: {{ $pct }}%"></div>
        </div>
    </div>
@empty
    <div class="py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Belum ada transaksi pengeluaran bulan ini.</div>
@endforelse
</div>

<div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
<span class="text-slate-400">Total {{ count($topCategories) }} Kategori Terbesar</span>
<span class="text-slate-700 dark:text-slate-300 font-semibold">Rp {{ number_format($topCategoriesTotal, 0, ',', '.') }} ({{ $topCategoriesPct }}%)</span>
</div>
</div>
</div>

<!-- 6. CONSUMER-GRADE ACTIVITY STREAM -->
<section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card space-y-6">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div>
<h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Aktivitas &amp; Riwayat Transaksi</h2>
<p class="text-xs text-slate-500 dark:text-slate-400">Pencatatan real-time transaksi akun Anda</p>
</div>
<a href="{{ route('transactions.index') }}" wire:navigate.hover class="px-4 py-2 rounded-full bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-semibold text-xs transition-all shadow-sm flex items-center gap-1.5 self-start sm:self-auto">
<span class="material-symbols-outlined text-[16px]">add</span>
<span>Catat Transaksi</span>
</a>
</div>

<!-- Activity List -->
<div class="divide-y divide-slate-100 dark:divide-slate-800">
@forelse($recentTransactions as $tx)
    @php
        $isIncome = $tx->type === 'income';
        $isTransfer = $tx->type === 'transfer';
        
        $icon = $isIncome ? 'payments' : ($isTransfer ? 'sync_alt' : 'shopping_bag');
        $iconBg = $isIncome ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200/50' : 
                  ($isTransfer ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300 border-indigo-200/50' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200/50');
        
        $amountColor = $isIncome ? 'text-emerald-600 dark:text-emerald-400' : ($isTransfer ? 'text-slate-600 dark:text-slate-400' : 'text-slate-900 dark:text-white');
        $prefix = $isIncome ? '+' : ($isTransfer ? '' : '-');
    @endphp
    <div class="py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 px-2 rounded-2xl transition-colors cursor-pointer group">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl {{ $iconBg }} border flex items-center justify-center group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-display font-bold text-sm text-slate-900 dark:text-white">{{ $tx->description ?? ($tx->category->name ?? 'Transaksi') }}</span>
                    @if($isIncome)
                        <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/50">
                            Masuk
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                    <span class="text-slate-600 dark:text-slate-300 font-medium">{{ $tx->category->name ?? 'Transfer' }}</span>
                    <span>•</span>
                    <span class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold text-[10px]">{{ $tx->account->name }}</span>
                    <span>•</span>
                    <span>{{ \Carbon\Carbon::parse($tx->transaction_date)->diffForHumans() }}</span>
                </div>
            </div>
        </div>
        <div class="text-right">
            <span class="font-display font-bold text-sm {{ $amountColor }} block">{{ $prefix }} Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
        </div>
    </div>
@empty
    <div class="py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Belum ada aktivitas transaksi.</div>
@endforelse
</div>

<div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center">
<a class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline py-1 px-3" href="{{ route('transactions.index') }}" wire:navigate.hover>
<span>Lihat Semua ({{ $totalTransactionsCount }}) Transaksi di Bulan {{ $currentMonthName }}</span>
<span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</a>
</div>
</section>

</div>
