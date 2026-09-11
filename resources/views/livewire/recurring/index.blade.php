<?php

use Livewire\Volt\Component;
use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $recurringList = [];
    public $accounts = [];
    public $categories = [];

    public $account_id = '';
    public $category_id = '';
    public $type = 'expense';
    public $amount = '';
    public $frequency = 'monthly';
    public $next_run_date = '';
    public $description = '';
    public $is_active = true;
    public $editingId = null;

    public $monthlyEstimatedExpense = 0;
    public $upcomingCount = 0;

    public function mount()
    {
        $this->next_run_date = Carbon::now()->toDateString();
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->accounts = $user->accounts()->orderBy('name')->get();
        $this->categories = $user->categories()->orderBy('name')->get();

        if (empty($this->account_id) && $this->accounts->isNotEmpty()) {
            $this->account_id = $this->accounts->first()->id;
        }

        $this->recurringList = $user->recurringTransactions()
            ->with(['account', 'category'])
            ->orderBy('next_run_date', 'asc')
            ->get();

        // Calculate estimated monthly expense from recurring
        $this->monthlyEstimatedExpense = $this->recurringList
            ->where('type', 'expense')
            ->where('is_active', true)
            ->sum(function ($r) {
                return match($r->frequency) {
                    'daily' => $r->amount * 30,
                    'weekly' => $r->amount * 4.33,
                    'monthly' => $r->amount,
                    'yearly' => $r->amount / 12,
                    default => $r->amount,
                };
            });

        // Due in the next 7 days
        $nextWeek = Carbon::now()->addDays(7)->toDateString();
        $this->upcomingCount = $this->recurringList
            ->where('is_active', true)
            ->filter(fn($r) => $r->next_run_date <= $nextWeek)
            ->count();
    }

    public function save()
    {
        $validated = $this->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $validated['category_id'] = $validated['category_id'] ?: null;
        $validated['is_active'] = (bool) $this->is_active;

        if ($this->editingId) {
            Auth::user()->recurringTransactions()->findOrFail($this->editingId)->update($validated);
        } else {
            Auth::user()->recurringTransactions()->create($validated);
        }

        $this->cancelEdit();
        $this->loadData();
    }

    public function edit($id)
    {
        $item = Auth::user()->recurringTransactions()->findOrFail($id);
        $this->editingId = $item->id;
        $this->account_id = $item->account_id;
        $this->category_id = $item->category_id ?? '';
        $this->type = $item->type;
        $this->amount = $item->amount;
        $this->frequency = $item->frequency;
        $this->next_run_date = $item->next_run_date->toDateString();
        $this->description = $item->description;
        $this->is_active = $item->is_active;
    }

    public function cancelEdit()
    {
        $this->reset(['category_id', 'amount', 'description', 'editingId']);
        $this->type = 'expense';
        $this->frequency = 'monthly';
        $this->next_run_date = Carbon::now()->toDateString();
        $this->is_active = true;
    }

    public function toggleActive($id)
    {
        $item = Auth::user()->recurringTransactions()->findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        $this->loadData();
    }

    public function delete($id)
    {
        Auth::user()->recurringTransactions()->findOrFail($id)->delete();
        $this->loadData();
    }

    /**
     * Immediately execute the recurring transaction and bump the next run date.
     */
    public function runNow($id)
    {
        $item = Auth::user()->recurringTransactions()->findOrFail($id);
        $account = $item->account;

        // 1. Create Transaction
        Transaction::create([
            'user_id' => Auth::id(),
            'account_id' => $item->account_id,
            'category_id' => $item->category_id,
            'type' => $item->type,
            'amount' => $item->amount,
            'transaction_date' => Carbon::now()->toDateString(),
            'description' => '[Rutin] ' . $item->description,
        ]);

        // 2. Adjust Balance
        if ($item->type === 'expense') {
            $account->decrement('balance', $item->amount);
        } elseif ($item->type === 'income') {
            $account->increment('balance', $item->amount);
        }

        // 3. Advance next_run_date
        $nextDate = Carbon::parse($item->next_run_date);
        $nextDate = match($item->frequency) {
            'daily' => $nextDate->addDay(),
            'weekly' => $nextDate->addWeek(),
            'monthly' => $nextDate->addMonth(),
            'yearly' => $nextDate->addYear(),
            default => $nextDate->addMonth(),
        };

        $item->update(['next_run_date' => $nextDate->toDateString()]);
        $this->loadData();
    }
}; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-2xl text-slate-900 dark:text-white tracking-tight">Transaksi Berulang (Recurring)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Otomasi pencatatan langganan, tagihan rutin, cicilan, atau gaji berkala</p>
        </div>
        <div class="flex items-center gap-2">
            @if($upcomingCount > 0)
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/50">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    {{ $upcomingCount }} Jatuh Tempo Minggu Ini
                </span>
            @endif
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Jadwal Aktif</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">sync</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-slate-900 dark:text-white mt-2">{{ $recurringList->where('is_active', true)->count() }} Jadwal</p>
            <p class="text-xs text-slate-400 mt-1">Dari {{ count($recurringList) }} total transaksi terdaftar</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Beban Rutin Bulanan</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2">Rp {{ number_format($monthlyEstimatedExpense, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Estimasi komitmen pengeluaran tetap per bulan</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Segera Jatuh Tempo</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">alarm</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ $upcomingCount }} Transaksi</p>
            <p class="text-xs text-slate-400 mt-1">Dalam 7 hari ke depan</p>
        </div>
    </div>

    <!-- Form Section -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                    {{ $editingId ? 'Edit Jadwal Rutin' : 'Tambah Jadwal Transaksi Rutin' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Misalnya tagihan WiFi, Netflix, pulsa, sewa tempat, atau tabungan rutin</p>
            </div>
            @if($editingId)
                <button wire:click="cancelEdit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 underline">Batal</button>
            @endif
        </div>

        <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama / Deskripsi Tagihan</label>
                <input type="text" wire:model="description" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Tagihan IndiHome, Langganan Spotify" required />
                @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipe</label>
                <select wire:model="type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="expense">Pengeluaran (Expense)</option>
                    <option value="income">Pemasukan (Income)</option>
                </select>
                @error('type') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal (Rp)</label>
                <input type="number" step="1000" wire:model="amount" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: 350000" required />
                @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dompet / Rekening</label>
                <select wire:model="account_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
                @error('account_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                <select wire:model="category_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">-- Tanpa Kategori --</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->type }})</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Frekuensi</label>
                <select wire:model="frequency" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="daily">Harian (Daily)</option>
                    <option value="weekly">Mingguan (Weekly)</option>
                    <option value="monthly">Bulanan (Monthly)</option>
                    <option value="yearly">Tahunan (Yearly)</option>
                </select>
                @error('frequency') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jatuh Tempo Pertama</label>
                <input type="date" wire:model="next_run_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                @error('next_run_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-sm transition-all shadow-md active:scale-95 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] mr-1">check</span>
                    <span>Simpan Jadwal Rutin</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Recurring List Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-4">Daftar Jadwal Transaksi</h2>

        @if(count($recurringList) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-800/60 text-slate-400 dark:text-slate-400 font-semibold tracking-wider">
                        <tr>
                            <th class="px-4 py-3 rounded-l-xl">Transaksi & Kategori</th>
                            <th class="px-4 py-3">Frekuensi</th>
                            <th class="px-4 py-3">Dompet</th>
                            <th class="px-4 py-3">Nominal</th>
                            <th class="px-4 py-3">Jatuh Tempo</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($recurringList as $item)
                            @php
                                $isIncome = $item->type === 'income';
                                $isDueSoon = Carbon::now()->diffInDays($item->next_run_date, false) <= 3 && $item->is_active;
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl {{ $isIncome ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600' }} flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[18px]">{{ $isIncome ? 'arrow_downward' : 'sync' }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $item->description }}</p>
                                            <p class="text-xs text-slate-400">{{ $item->category->name ?? 'Tanpa Kategori' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 capitalize">
                                        {{ $item->frequency }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ $item->account->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-bold {{ $isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                        {{ $isIncome ? '+' : '-' }} Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs {{ $isDueSoon ? 'text-amber-600 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                            {{ $item->next_run_date->format('d M Y') }}
                                        </span>
                                        @if($isDueSoon)
                                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <button wire:click="toggleActive({{ $item->id }})" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300/40' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Dijeda' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button wire:click="runNow({{ $item->id }})" title="Jalankan Transaksi Sekarang" class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 text-xs font-bold transition-colors inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">play_arrow</span>
                                            <span>Jalankan</span>
                                        </button>
                                        <button wire:click="edit({{ $item->id }})" class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button wire:confirm="Hapus jadwal transaksi ini?" wire:click="delete({{ $item->id }})" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-10 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-[28px]">sync</span>
                </div>
                <h3 class="font-display font-bold text-sm text-slate-800 dark:text-slate-200">Belum Ada Transaksi Rutin</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">Daftarkan tagihan bulanan atau pemasukan berkala Anda di atas.</p>
            </div>
        @endif
    </div>
</div>

