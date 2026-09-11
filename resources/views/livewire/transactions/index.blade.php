<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Services\FinanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

new class extends Component {
    use WithFileUploads;

    public $transactions;
    public $accounts;
    public $categories;

    // Form inputs
    public $account_id = '';
    public $destination_account_id = '';
    public $category_id = '';
    public $type = 'expense';
    public $amount = '';
    public $transaction_date = '';
    public $description = '';
    public $notes = '';
    public $attachment;
    public $existingAttachment = null;
    public $tags = '';

    public $editingId = null;
    public $showForm = false;

    // Filter properties
    public $search = '';
    public $filterType = 'all';
    public $filterAccount = 'all';
    public $filterCategory = 'all';
    public $filterPeriod = 'this_month';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $filterWithAttachment = false;

    // Import modal
    public $showImportModal = false;
    public $csvFile;

    // Lightbox modal for receipt view
    public $previewAttachmentUrl = null;
    public $previewAttachmentTitle = '';

    public function mount()
    {
        $this->transaction_date = date('Y-m-d');
        $this->filterDateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->filterDateTo = Carbon::now()->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function loadData()
    {
        $query = Auth::user()->transactions()->with(['account', 'destinationAccount', 'category']);

        // Search text
        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        // Filter Type
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        // Filter Account
        if ($this->filterAccount !== 'all') {
            $query->where('account_id', $this->filterAccount);
        }

        // Filter Category
        if ($this->filterCategory !== 'all') {
            $query->where('category_id', $this->filterCategory);
        }

        // Filter Period
        if ($this->filterPeriod === 'this_month') {
            $query->whereBetween('transaction_date', [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()]);
        } elseif ($this->filterPeriod === 'last_month') {
            $query->whereBetween('transaction_date', [Carbon::now()->subMonth()->startOfMonth()->toDateString(), Carbon::now()->subMonth()->endOfMonth()->toDateString()]);
        } elseif ($this->filterPeriod === 'last_30_days') {
            $query->whereBetween('transaction_date', [Carbon::now()->subDays(30)->toDateString(), Carbon::now()->toDateString()]);
        } elseif ($this->filterPeriod === 'custom' && !empty($this->filterDateFrom) && !empty($this->filterDateTo)) {
            $query->whereBetween('transaction_date', [$this->filterDateFrom, $this->filterDateTo]);
        }

        // Filter Attachment
        if ($this->filterWithAttachment) {
            $query->whereNotNull('attachment')->where('attachment', '!=', '');
        }

        $this->transactions = $query->latest('transaction_date')->latest('id')->get();
        $this->accounts = Auth::user()->accounts()->orderBy('name')->get();
        $this->categories = Category::where('user_id', Auth::id())->orWhere('is_default', true)->orderBy('type')->orderBy('name')->get();
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'filter') || $propertyName === 'search') {
            if ($this->filterPeriod === 'this_month') {
                $this->filterDateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->filterDateTo = Carbon::now()->endOfMonth()->toDateString();
            } elseif ($this->filterPeriod === 'last_month') {
                $this->filterDateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->filterDateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
            } elseif ($this->filterPeriod === 'last_30_days') {
                $this->filterDateFrom = Carbon::now()->subDays(30)->toDateString();
                $this->filterDateTo = Carbon::now()->toDateString();
            }
            $this->loadData();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterType = 'all';
        $this->filterAccount = 'all';
        $this->filterCategory = 'all';
        $this->filterPeriod = 'this_month';
        $this->filterDateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->filterDateTo = Carbon::now()->endOfMonth()->toDateString();
        $this->filterWithAttachment = false;
        $this->loadData();
    }

    public function getFilteredCategoriesProperty()
    {
        return $this->categories->where('type', $this->type);
    }

    public function save(FinanceService $financeService)
    {
        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:2048',
        ];

        if ($this->type === 'transfer') {
            $rules['destination_account_id'] = 'required|exists:accounts,id|different:account_id';
            $this->category_id = null;
        } else {
            $rules['category_id'] = 'required|exists:categories,id';
            $this->destination_account_id = null;
        }

        $validated = $this->validate($rules);
        
        $validated['user_id'] = Auth::id();
        $validated['category_id'] = $this->type === 'transfer' ? null : $this->category_id;
        $validated['destination_account_id'] = $this->type === 'transfer' ? $this->destination_account_id : null;

        // Handle attachment upload
        if ($this->attachment) {
            $path = $this->attachment->store('attachments', 'public');
            $validated['attachment'] = $path;
        } elseif ($this->editingId && $this->existingAttachment) {
            $validated['attachment'] = $this->existingAttachment;
        } else {
            $validated['attachment'] = null;
        }

        if ($this->editingId) {
            $transaction = Auth::user()->transactions()->findOrFail($this->editingId);
            $financeService->updateTransaction($transaction, $validated);
            session()->flash('success', 'Transaksi berhasil diperbarui.');
        } else {
            $financeService->createTransaction($validated);
            session()->flash('success', 'Transaksi baru berhasil dicatat.');
        }

        $this->resetForm();
        $this->loadData();
    }

    public function edit($id)
    {
        $transaction = Auth::user()->transactions()->findOrFail($id);
        $this->editingId = $transaction->id;
        $this->account_id = $transaction->account_id;
        $this->destination_account_id = $transaction->destination_account_id;
        $this->category_id = $transaction->category_id;
        $this->type = $transaction->type;
        $this->amount = $transaction->amount;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
        $this->description = $transaction->description;
        $this->notes = $transaction->notes;
        $this->existingAttachment = $transaction->attachment;
        $this->attachment = null;
        $this->showForm = true;
    }

    public function removeAttachment()
    {
        $this->attachment = null;
        $this->existingAttachment = null;
    }

    public function resetForm()
    {
        $this->reset(['account_id', 'destination_account_id', 'category_id', 'amount', 'description', 'notes', 'attachment', 'existingAttachment', 'editingId']);
        $this->type = 'expense';
        $this->transaction_date = date('Y-m-d');
        $this->showForm = false;
    }

    public function delete($id, FinanceService $financeService)
    {
        $transaction = Auth::user()->transactions()->findOrFail($id);
        if ($transaction->attachment) {
            Storage::disk('public')->delete($transaction->attachment);
        }
        $financeService->deleteTransaction($transaction);
        session()->flash('success', 'Transaksi berhasil dihapus.');
        $this->loadData();
    }

    public function viewReceipt($id)
    {
        $transaction = Auth::user()->transactions()->findOrFail($id);
        if ($transaction->attachment) {
            $this->previewAttachmentUrl = asset('storage/' . $transaction->attachment);
            $this->previewAttachmentTitle = $transaction->description . ' (' . $transaction->transaction_date->format('d M Y') . ')';
        }
    }

    public function closeReceipt()
    {
        $this->previewAttachmentUrl = null;
        $this->previewAttachmentTitle = '';
    }

    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="format_import_transaksi.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Tanggal', 'Tipe', 'Dompet', 'Kategori', 'Nominal', 'Deskripsi', 'Catatan']);
            fputcsv($handle, [now()->format('Y-m-d'), 'expense', 'BCA', 'Makanan & Minuman', '45000', 'Makan Siang', 'Nasi padang']);
            fputcsv($handle, [now()->format('Y-m-d'), 'income', 'Gopay', 'Gaji & Pendapatan', '5000000', 'Gaji Bulanan', 'Transfer gaji']);
            fclose($handle);
        }, 200, $headers);
    }

    public function importCsv(FinanceService $financeService)
    {
        $this->validate([
            'csvFile' => 'required|file|max:5120',
        ]);

        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }
        
        $header = fgetcsv($file);
        $importedCount = 0;
        $user = Auth::user();
        $accounts = $user->accounts()->pluck('id', 'name')->toArray();
        $categories = Category::where('user_id', $user->id)->orWhere('is_default', true)->pluck('id', 'name')->toArray();
        $defaultAccount = $user->accounts()->first();
        $defaultCategory = Category::first();

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 5 || empty(trim($row[0]))) continue;
            
            $date = trim($row[0]);
            $type = strtolower(trim($row[1]));
            if (!in_array($type, ['income', 'expense', 'transfer'])) $type = 'expense';
            
            $accountName = trim($row[2]);
            $accountId = $accounts[$accountName] ?? ($defaultAccount ? $defaultAccount->id : null);
            if (!$accountId) continue;

            $categoryName = trim($row[3]);
            $categoryId = $categories[$categoryName] ?? ($defaultCategory ? $defaultCategory->id : null);
            
            $amount = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^0-9.,]/', '', $row[4]));
            if ($amount <= 0) continue;

            $description = !empty($row[5]) ? trim($row[5]) : 'Import Transaksi';
            $notes = !empty($row[6]) ? trim($row[6]) : null;

            $financeService->createTransaction([
                'user_id' => $user->id,
                'account_id' => $accountId,
                'category_id' => $type === 'transfer' ? null : $categoryId,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $date,
                'description' => $description,
                'notes' => $notes,
            ]);
            $importedCount++;
        }
        fclose($file);

        $this->reset('csvFile');
        $this->showImportModal = false;
        session()->flash('success', "Berhasil mengimpor {$importedCount} transaksi ke dalam pembukuan.");
        $this->loadData();
    }
}; ?>

<div class="space-y-8 pb-12">
    <!-- Header Banner & Action Pills -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                <span>Buku Kas &amp; Riwayat Lengkap</span>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Aktivitas &amp; Transaksi
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Kelola mutasi, lampirkan struk belanja, dan ekspor pembukuan keuangan Anda.
            </p>
        </div>

        <!-- Global Action Toolbar -->
        <div class="flex flex-wrap items-center gap-2.5">
            <button wire:click="$set('showImportModal', true)" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[16px]">upload_file</span>
                <span>Import CSV</span>
            </button>

            <!-- Export CSV URL with dynamic query parameters -->
            @php
                $exportParams = [
                    'search' => $search,
                    'type' => $filterType !== 'all' ? $filterType : null,
                    'account_id' => $filterAccount !== 'all' ? $filterAccount : null,
                    'category_id' => $filterCategory !== 'all' ? $filterCategory : null,
                    'date_from' => $filterDateFrom ?: null,
                    'date_to' => $filterDateTo ?: null,
                ];
            @endphp
            <a href="{{ route('transactions.export.csv', array_filter($exportParams)) }}" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[16px]">download</span>
                <span>Export CSV</span>
            </a>

            <a href="{{ route('transactions.export.print', array_filter($exportParams)) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[16px]">print</span>
                <span>Cetak / PDF</span>
            </a>

            <button wire:click="$toggle('showForm')" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-indigo-500/20 active:scale-95">
                <span class="material-symbols-outlined text-[16px]">{{ $showForm ? 'close' : 'add' }}</span>
                <span>{{ $showForm ? 'Tutup Form' : 'Tambah Transaksi' }}</span>
            </button>
        </div>
    </div>

    <!-- Flash Alert -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-xs font-bold opacity-70 hover:opacity-100">×</button>
        </div>
    @endif

    <!-- 1. FORM TAMBAH / EDIT TRANSAKSI -->
    @if($showForm)
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">{{ $editingId ? 'edit_note' : 'add_circle' }}</span>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                            {{ $editingId ? 'Edit Data Transaksi' : 'Catat Transaksi Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan saldo akun dan kategori terisi dengan benar</p>
                    </div>
                </div>
                <button wire:click="resetForm" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-white">Batal</button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <!-- Tipe Selector Pill Tabs -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Tipe Transaksi</label>
                    <div class="grid grid-cols-3 gap-2 max-w-md">
                        <button type="button" wire:click="$set('type', 'expense')" class="py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 {{ $type === 'expense' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                            <span class="material-symbols-outlined text-[16px]">arrow_outward</span>
                            <span>Pengeluaran</span>
                        </button>
                        <button type="button" wire:click="$set('type', 'income')" class="py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 {{ $type === 'income' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                            <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                            <span>Pemasukan</span>
                        </button>
                        <button type="button" wire:click="$set('type', 'transfer')" class="py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 {{ $type === 'transfer' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                            <span class="material-symbols-outlined text-[16px]">sync_alt</span>
                            <span>Transfer</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Nominal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal (Rp) *</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-slate-400">Rp</span>
                            <input wire:model="amount" type="number" step="any" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-display font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="0" required>
                        </div>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <!-- Tanggal Transaksi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Transaksi *</label>
                        <input wire:model="transaction_date" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                        <x-input-error :messages="$errors->get('transaction_date')" class="mt-1" />
                    </div>

                    <!-- Dompet Asal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            {{ $type === 'transfer' ? 'Dompet Sumber Dana *' : 'Dompet / Rekening *' }}
                        </label>
                        <select wire:model="account_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                            <option value="">-- Pilih Dompet --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('account_id')" class="mt-1" />
                    </div>

                    <!-- Dompet Tujuan (Khusus Transfer) -->
                    @if($type === 'transfer')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dompet Tujuan Transfer *</label>
                            <select wire:model="destination_account_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                                <option value="">-- Pilih Dompet Tujuan --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('destination_account_id')" class="mt-1" />
                        </div>
                    @else
                        <!-- Kategori (Income / Expense) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori *</label>
                            <select wire:model="category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($this->filteredCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                        </div>
                    @endif

                    <!-- Deskripsi Singkat -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Transaksi *</label>
                        <input wire:model="description" type="text" placeholder="Contoh: Belanja Bulanan di Supermarket" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                        <input wire:model="notes" type="text" placeholder="Nomor invoice, keterangan promo, atau rincian item" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>

                    <!-- Lampiran Struk / Bukti Transfer (WithFileUploads) -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Foto Struk / Bukti Pembayaran (JPG, PNG, WEBP, PDF maks 2MB)
                        </label>
                        <div class="flex items-center gap-4">
                            <input wire:model="attachment" type="file" accept="image/*,.pdf" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                            
                            @if($attachment)
                                <div class="relative flex-shrink-0 group">
                                    <img src="{{ $attachment->temporaryUrl() }}" class="w-12 h-12 rounded-xl object-cover border border-indigo-200 shadow-xs">
                                    <button type="button" wire:click="removeAttachment" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs shadow-sm">×</button>
                                </div>
                            @elseif($existingAttachment)
                                <div class="relative flex-shrink-0 flex items-center gap-2 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <img src="{{ asset('storage/' . $existingAttachment) }}" class="w-10 h-10 rounded-lg object-cover">
                                    <span class="text-[11px] text-slate-500">Struk tersimpan</span>
                                    <button type="button" wire:click="removeAttachment" class="text-rose-500 text-xs font-bold px-1 hover:underline">Hapus</button>
                                </div>
                            @endif
                        </div>
                        <div wire:loading wire:target="attachment" class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1">Mengunggah file struk...</div>
                        <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" wire:click="resetForm" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all">
                        {{ $editingId ? 'Simpan Perubahan' : 'Catat Transaksi Sekarang' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- 2. ADVANCED MULTI-FILTER TOOLBAR -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-card space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-indigo-600 dark:text-indigo-400">filter_alt</span>
                <span class="font-display font-bold text-sm text-slate-900 dark:text-white">Filter &amp; Pencarian Canggih</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                    {{ $transactions->count() }} Transaksi
                </span>
            </div>
            <button wire:click="resetFilters" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">restart_alt</span>
                <span>Reset Filter</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Text -->
            <div class="relative lg:col-span-1">
                <span class="material-symbols-outlined text-[18px] absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari deskripsi / catatan..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            <!-- Filter Type -->
            <div>
                <select wire:model.live="filterType" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all">Semua Tipe (Semua)</option>
                    <option value="income">Pemasukan (+)</option>
                    <option value="expense">Pengeluaran (-)</option>
                    <option value="transfer">Transfer (⇄)</option>
                </select>
            </div>

            <!-- Filter Account -->
            <div>
                <select wire:model.live="filterAccount" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all">Semua Dompet / Akun</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Category -->
            <div>
                <select wire:model.live="filterCategory" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Period Preset -->
            <div>
                <select wire:model.live="filterPeriod" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="last_30_days">30 Hari Terakhir</option>
                    <option value="all">Semua Waktu</option>
                    <option value="custom">Rentang Kustom...</option>
                </select>
            </div>
        </div>

        <!-- Custom Date Range & Attachment Toggle -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            @if($filterPeriod === 'custom')
                <div class="flex items-center gap-2">
                    <span class="text-slate-500 font-medium">Dari:</span>
                    <input wire:model.live="filterDateFrom" type="date" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                    <span class="text-slate-500 font-medium">Sampai:</span>
                    <input wire:model.live="filterDateTo" type="date" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                </div>
            @else
                <div class="text-slate-400">
                    Menampilkan periode: <strong class="text-slate-700 dark:text-slate-300">{{ Carbon::parse($filterDateFrom)->translatedFormat('d M Y') }} - {{ Carbon::parse($filterDateTo)->translatedFormat('d M Y') }}</strong>
                </div>
            @endif

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input wire:model.live="filterWithAttachment" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-indigo-500">attachment</span>
                    <span>Hanya transaksi yang ada struk</span>
                </span>
            </label>
        </div>
    </div>

    <!-- 3. RIWAYAT TRANSAKSI TABLE / LIST -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($transactions as $t)
                @php
                    $isIncome = $t->type === 'income';
                    $isTransfer = $t->type === 'transfer';
                    $icon = $isIncome ? 'payments' : ($isTransfer ? 'sync_alt' : 'shopping_bag');
                    $iconBg = $isIncome ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200/50' : 
                              ($isTransfer ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300 border-indigo-200/50' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200/50');
                    $amountColor = $isIncome ? 'text-emerald-600 dark:text-emerald-400' : ($isTransfer ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-900 dark:text-white');
                    $prefix = $isIncome ? '+' : ($isTransfer ? '' : '-');
                @endphp
                <div class="py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/40 px-3 rounded-2xl transition-all group">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-2xl {{ $iconBg }} border flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-display font-bold text-sm text-slate-900 dark:text-white truncate">
                                    {{ $t->description }}
                                </span>
                                @if($t->attachment)
                                    <button wire:click="viewReceipt({{ $t->id }})" title="Lihat Foto Struk" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-[10px] font-bold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-colors">
                                        <span class="material-symbols-outlined text-[13px]">image</span>
                                        <span>Struk</span>
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5 flex-wrap">
                                <span class="text-slate-600 dark:text-slate-300 font-medium">
                                    {{ $t->category->name ?? ($isTransfer ? 'Transfer Antar Dompet' : 'Tanpa Kategori') }}
                                </span>
                                <span>•</span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-semibold">
                                    {{ $t->account->name }}
                                    @if($isTransfer && $t->destinationAccount)
                                        → {{ $t->destinationAccount->name }}
                                    @endif
                                </span>
                                <span>•</span>
                                <span>{{ $t->transaction_date->translatedFormat('d M Y') }}</span>
                                @if($t->notes)
                                    <span class="hidden sm:inline italic text-slate-400 text-[11px]">"{{ $t->notes }}"</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                        <div class="text-right">
                            <span class="font-display font-bold text-sm sm:text-base {{ $amountColor }} block">
                                {{ $prefix }}Rp {{ number_format($t->amount, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] uppercase font-bold tracking-wider {{ $isIncome ? 'text-emerald-500' : ($isTransfer ? 'text-indigo-500' : 'text-rose-400') }}">
                                {{ $isIncome ? 'Masuk' : ($isTransfer ? 'Transfer' : 'Keluar') }}
                            </span>
                        </div>

                        <!-- Action Controls -->
                        <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                            <button wire:click="edit({{ $t->id }})" title="Edit" class="p-1.5 rounded-xl hover:bg-indigo-50 dark:hover:bg-slate-800 text-slate-400 hover:text-indigo-600 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button wire:confirm="Hapus transaksi ini? Saldo dompet akan disesuaikan kembali." wire:click="delete({{ $t->id }})" title="Hapus" class="p-1.5 rounded-xl hover:bg-rose-50 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-500 dark:text-slate-400">
                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2 block">receipt</span>
                    <p class="text-sm font-semibold">Tidak ada transaksi yang cocok dengan filter yang dipilih.</p>
                    <button wire:click="resetFilters" class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Bersihkan Filter
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 4. LIGHTBOX / MODAL PREVIEW STRUK -->
    @if($previewAttachmentUrl)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="relative max-w-2xl w-full bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-float border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-indigo-600">receipt</span>
                        <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white truncate">
                            {{ $previewAttachmentTitle }}
                        </h3>
                    </div>
                    <button wire:click="closeReceipt" class="p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="flex justify-center bg-slate-50 dark:bg-slate-950/50 p-3 rounded-2xl max-h-[70vh] overflow-auto">
                    <img src="{{ $previewAttachmentUrl }}" class="max-h-[65vh] w-auto rounded-xl object-contain shadow-sm" alt="Foto Struk">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ $previewAttachmentUrl }}" download target="_blank" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">download</span>
                        <span>Buka / Unduh Gambar Penuh</span>
                    </a>
                    <button wire:click="closeReceipt" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- 5. MODAL IMPORT CSV -->
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="relative max-w-lg w-full bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-float border border-slate-200 dark:border-slate-800 p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">upload_file</span>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Import Data Transaksi</h3>
                            <p class="text-xs text-slate-400">Unggah file CSV mutasi bank atau pembukuan</p>
                        </div>
                    </div>
                    <button wire:click="$set('showImportModal', false)" class="p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form wire:submit="importCsv" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-800/40 text-xs text-slate-600 dark:text-slate-300 space-y-2">
                        <div class="font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">info</span>
                            <span>Format File CSV</span>
                        </div>
                        <p>Pastikan file CSV memiliki kolom: <code>Tanggal, Tipe, Dompet, Kategori, Nominal, Deskripsi, Catatan</code></p>
                        <button type="button" wire:click="downloadSampleCsv" class="inline-flex items-center gap-1 text-indigo-700 dark:text-indigo-400 font-bold hover:underline">
                            <span class="material-symbols-outlined text-[14px]">file_download</span>
                            <span>Unduh Contoh Template CSV</span>
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih File CSV (.csv)</label>
                        <input wire:model="csvFile" type="file" accept=".csv,text/csv" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <x-input-error :messages="$errors->get('csvFile')" class="mt-1" />
                        <div wire:loading wire:target="csvFile" class="text-[11px] text-indigo-600 mt-1">Membaca file...</div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="$set('showImportModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                            <span>Mulai Import</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
