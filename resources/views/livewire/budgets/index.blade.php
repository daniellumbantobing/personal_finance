<?php

use Livewire\Volt\Component;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $budgets = [];
    public $categories = [];
    
    public $category_id = '';
    public $amount = '';
    public $period = 'monthly';
    public $start_date = '';
    public $end_date = '';
    public $editingId = null;

    public $totalBudget = 0;
    public $totalSpent = 0;
    public $currency = 'IDR';

    public function mount()
    {
        $this->currency = Auth::user()->currency ?? 'IDR';
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->categories = $user->categories()->where('type', 'expense')->orderBy('name')->get();
        
        $this->budgets = $user->budgets()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->totalBudget = $this->budgets->sum('amount');
        $this->totalSpent = $this->budgets->sum(fn($b) => $b->spent);
    }

    public function save()
    {
        $validated = $this->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'period' => 'required|in:monthly,weekly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($this->editingId) {
            Auth::user()->budgets()->findOrFail($this->editingId)->update($validated);
        } else {
            Auth::user()->budgets()->create($validated);
        }

        $this->reset(['category_id', 'amount', 'editingId']);
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function edit($id)
    {
        $budget = Auth::user()->budgets()->findOrFail($id);
        $this->editingId = $budget->id;
        $this->category_id = $budget->category_id;
        $this->amount = $budget->amount;
        $this->period = $budget->period;
        $this->start_date = $budget->start_date->toDateString();
        $this->end_date = $budget->end_date->toDateString();
    }

    public function delete($id)
    {
        Auth::user()->budgets()->findOrFail($id)->delete();
        $this->loadData();
    }

    public function cancelEdit()
    {
        $this->reset(['category_id', 'amount', 'editingId']);
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();
    }
}; ?>

<div class="space-y-8">
    <!-- Header Summary Bento -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-2xl text-slate-900 dark:text-white tracking-tight">Anggaran Bulanan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pantau batas belanja per kategori agar keuangan tetap sehat</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/50">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                Periode: {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Anggaran</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-slate-900 dark:text-white mt-2">Rp {{ number_format($totalBudget, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ count($budgets) }} Pos Anggaran Aktif</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terpakai Saat Ini</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">shopping_cart_checkout</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2">Rp {{ number_format($totalSpent, 0, ',', '.') }}</p>
            @php
                $overallPct = $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100)) : 0;
            @endphp
            <p class="text-xs text-slate-400 mt-1">{{ $overallPct }}% dari total limit anggaran</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Anggaran</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
            </div>
            @php
                $remaining = max(0, $totalBudget - $totalSpent);
            @endphp
            <p class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalSpent > $totalBudget ? 'Overbudget Rp ' . number_format($totalSpent - $totalBudget, 0, ',', '.') : 'Aman untuk bulan ini' }}</p>
        </div>
    </div>

    <!-- Form Section -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                    {{ $editingId ? 'Edit Anggaran Kategori' : 'Pasang Anggaran Baru' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tentukan batas pengeluaran untuk kategori tertentu</p>
            </div>
            @if($editingId)
                <button wire:click="cancelEdit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 underline">Batal</button>
            @endif
        </div>

        <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Pengeluaran</label>
                <select wire:model="category_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Batas Nominal (Limit)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">Rp</span>
                    <input type="number" step="1000" wire:model="amount" class="w-full pl-9 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: 1500000" required />
                </div>
                @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                <select wire:model="period" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="monthly">Bulanan</option>
                    <option value="weekly">Mingguan</option>
                    <option value="yearly">Tahunan</option>
                </select>
                @error('period') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dari Tanggal</label>
                <input type="date" wire:model="start_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                @error('start_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model="end_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-sm transition-all shadow-md active:scale-95 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] mr-1">check</span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Budgets List Cards -->
    <div class="space-y-4">
        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Daftar Anggaran Berjalan</h2>
        
        @if(count($budgets) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($budgets as $b)
                    @php
                        $spent = $b->spent;
                        $pct = $b->percentage;
                        $isOver = $spent > $b->amount;
                        $isWarning = $pct >= 75 && !$isOver;

                        $barColor = $isOver ? 'bg-rose-500' : ($isWarning ? 'bg-amber-500' : 'bg-emerald-500');
                        $badgeColor = $isOver ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/40' : 
                                      ($isWarning ? 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900/40' : 
                                      'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/40');
                        $statusText = $isOver ? 'Overbudget!' : ($isWarning ? 'Mendekati Limit' : 'Aman');
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card flex flex-col justify-between hover:border-indigo-200 dark:hover:border-slate-700 transition-all">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[20px]">{{ $b->category->icon ?? 'category' }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white">{{ $b->category->name }}</h3>
                                        <span class="text-[11px] text-slate-400 capitalize">{{ $b->period }} ({{ $b->start_date->format('d M') }} - {{ $b->end_date->format('d M') }})</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeColor }}">
                                    {{ $statusText }}
                                </span>
                            </div>

                            <!-- Progress & Numbers -->
                            <div class="space-y-2 my-4">
                                <div class="flex items-baseline justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Terpakai</span>
                                    <div>
                                        <span class="font-bold {{ $isOver ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                                            Rp {{ number_format($spent, 0, ',', '.') }}
                                        </span>
                                        <span class="text-slate-400">/ Rp {{ number_format($b->amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                    <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span>{{ $pct }}% terpakai</span>
                                    <span>Sisa: Rp {{ number_format(max(0, $b->amount - $spent), 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2 text-xs">
                            <button wire:click="edit({{ $b->id }})" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button wire:confirm="Hapus anggaran ini?" wire:click="delete({{ $b->id }})" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-12 border border-slate-200/80 dark:border-slate-800 text-center">
                <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-[32px]">savings</span>
                </div>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Belum Ada Anggaran</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">Buat batas anggaran untuk pos pengeluaran Anda agar FinAI dapat mengingatkan bila belanja berlebih.</p>
            </div>
        @endif
    </div>
</div>

