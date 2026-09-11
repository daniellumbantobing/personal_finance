<?php

use Livewire\Volt\Component;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Account;
use App\Services\FinanceService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $debts;
    public $accounts;

    public $activeTab = 'payable'; // 'payable', 'receivable', 'paid'

    // Form modal state
    public $showFormModal = false;
    public $editingId = null;
    public $type = 'payable';
    public $person_name = '';
    public $total_amount = '';
    public $due_date = '';
    public $account_id = '';
    public $description = '';
    public $affect_wallet = false;

    // Payment modal state
    public $showPaymentModal = false;
    public $selectedDebt = null;
    public $payment_amount = '';
    public $payment_account_id = '';
    public $payment_date = '';
    public $payment_notes = '';

    public function mount()
    {
        $this->due_date = Carbon::now()->addMonth()->toDateString();
        $this->payment_date = date('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->debts = $user->debts()->with(['account', 'payments.account'])->latest('id')->get();
        $this->accounts = $user->accounts()->orderBy('name')->get();

        if ($this->accounts->isNotEmpty() && empty($this->account_id)) {
            $this->account_id = $this->accounts->first()->id;
        }
        if ($this->accounts->isNotEmpty() && empty($this->payment_account_id)) {
            $this->payment_account_id = $this->accounts->first()->id;
        }
    }

    public function openCreateModal($type = 'payable')
    {
        $this->resetForm();
        $this->type = $type;
        $this->showFormModal = true;
    }

    public function edit($id)
    {
        $debt = Auth::user()->debts()->findOrFail($id);
        $this->editingId = $debt->id;
        $this->type = $debt->type;
        $this->person_name = $debt->person_name;
        $this->total_amount = $debt->total_amount;
        $this->due_date = $debt->due_date ? $debt->due_date->format('Y-m-d') : '';
        $this->account_id = $debt->account_id;
        $this->description = $debt->description;
        $this->affect_wallet = false;
        $this->showFormModal = true;
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'person_name', 'total_amount', 'description', 'affect_wallet']);
        $this->type = 'payable';
        $this->due_date = Carbon::now()->addMonth()->toDateString();
        $this->showFormModal = false;
    }

    public function save(FinanceService $financeService)
    {
        $rules = [
            'type' => 'required|in:payable,receivable',
            'person_name' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ];

        $validated = $this->validate($rules);
        $validated['user_id'] = Auth::id();
        $validated['due_date'] = !empty($this->due_date) ? $this->due_date : null;

        if ($this->editingId) {
            $debt = Auth::user()->debts()->findOrFail($this->editingId);
            $debt->update($validated);
            session()->flash('success', 'Data pinjaman berhasil diperbarui.');
        } else {
            $financeService->createDebt($validated, (bool) $this->affect_wallet);
            session()->flash('success', 'Catatan pinjaman baru berhasil disimpan.');
        }

        $this->resetForm();
        $this->loadData();
    }

    public function delete($id)
    {
        $debt = Auth::user()->debts()->findOrFail($id);
        $debt->delete();
        session()->flash('success', 'Pinjaman berhasil dihapus.');
        $this->loadData();
    }

    public function openPaymentModal($id)
    {
        $this->selectedDebt = Auth::user()->debts()->with('payments.account')->findOrFail($id);
        $this->payment_amount = $this->selectedDebt->remaining_amount;
        $this->payment_date = date('Y-m-d');
        $this->payment_notes = '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->selectedDebt = null;
        $this->payment_amount = '';
        $this->payment_notes = '';
    }

    public function recordPayment(FinanceService $financeService)
    {
        if (!$this->selectedDebt) return;

        $maxAmount = $this->selectedDebt->remaining_amount;
        $rules = [
            'payment_account_id' => 'required|exists:accounts,id',
            'payment_amount' => 'required|numeric|min:1|max:' . $maxAmount,
            'payment_date' => 'required|date',
            'payment_notes' => 'nullable|string',
        ];

        $this->validate($rules);

        $financeService->recordDebtPayment($this->selectedDebt, [
            'account_id' => $this->payment_account_id,
            'amount' => (float) $this->payment_amount,
            'payment_date' => $this->payment_date,
            'notes' => $this->payment_notes,
        ]);

        session()->flash('success', 'Pembayaran cicilan berhasil dicatat & saldo dompet telah disesuaikan.');
        $this->closePaymentModal();
        $this->loadData();
    }

    public function deletePayment($paymentId, FinanceService $financeService)
    {
        $payment = DebtPayment::whereHas('debt', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($paymentId);

        $financeService->deleteDebtPayment($payment);
        session()->flash('success', 'Histori pembayaran berhasil dibatalkan & saldo dipulihkan.');

        if ($this->selectedDebt) {
            $this->selectedDebt->refresh();
        }
        $this->loadData();
    }
}; ?>

<div class="space-y-8 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                <span class="material-symbols-outlined text-[15px]">handshake</span>
                <span>Manajemen Hutang &amp; Piutang</span>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Hutang &amp; Piutang
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Pantau kewajiban bayar Anda serta hak tagih ke orang lain beserta cicilannya secara real-time.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button wire:click="openCreateModal('payable')" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-rose-600/20 active:scale-95">
                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                <span>+ Catat Hutang</span>
            </button>
            <button wire:click="openCreateModal('receivable')" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-emerald-600/20 active:scale-95">
                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                <span>+ Catat Piutang</span>
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

    <!-- 1. BENTO STATS CARDS -->
    @php
        $totalPayableRemaining = $debts->where('type', 'payable')->where('status', '!=', 'paid')->sum(fn($d) => $d->remaining_amount);
        $totalReceivableRemaining = $debts->where('type', 'receivable')->where('status', '!=', 'paid')->sum(fn($d) => $d->remaining_amount);
        $dueSoonCount = $debts->filter(fn($d) => $d->status !== 'paid' && $d->due_date && ($d->is_overdue || ($d->due_days_left !== null && $d->due_days_left <= 7)))->count();
        $overdueCount = $debts->filter(fn($d) => $d->is_overdue)->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        <!-- Hutang Card -->
        <div class="bg-gradient-to-br from-rose-50/80 via-white to-white dark:from-rose-950/30 dark:via-slate-900 dark:to-slate-900 rounded-3xl p-6 sm:p-7 border border-rose-200/80 dark:border-rose-900/40 shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">outbox</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                    Kewajiban Anda
                </span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Total Hutang Belum Lunas</span>
                <span class="font-display font-extrabold text-2xl sm:text-3xl text-rose-600 dark:text-rose-400 tracking-tight">
                    Rp {{ number_format($totalPayableRemaining, 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-4 pt-3 border-t border-rose-100 dark:border-rose-900/40 text-xs text-slate-500">
                Dari {{ $debts->where('type', 'payable')->where('status', '!=', 'paid')->count() }} pinjaman aktif
            </div>
        </div>

        <!-- Piutang Card -->
        <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white dark:from-emerald-950/30 dark:via-slate-900 dark:to-slate-900 rounded-3xl p-6 sm:p-7 border border-emerald-200/80 dark:border-emerald-900/40 shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">move_to_inbox</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    Hak Tagih Anda
                </span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Total Piutang Belum Tertagih</span>
                <span class="font-display font-extrabold text-2xl sm:text-3xl text-emerald-600 dark:text-emerald-400 tracking-tight">
                    Rp {{ number_format($totalReceivableRemaining, 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-4 pt-3 border-t border-emerald-100 dark:border-emerald-900/40 text-xs text-slate-500">
                Dari {{ $debts->where('type', 'receivable')->where('status', '!=', 'paid')->count() }} orang/pihak
            </div>
        </div>

        <!-- Jatuh Tempo & Alert -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-2xl {{ $overdueCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">notification_important</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $overdueCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                    Status Jatuh Tempo
                </span>
            </div>
            <div>
                <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">Tempo Dekat / Terlewat</span>
                <div class="flex items-baseline gap-2">
                    <span class="font-display font-extrabold text-2xl sm:text-3xl text-slate-900 dark:text-white">
                        {{ $dueSoonCount }}
                    </span>
                    <span class="text-xs font-semibold text-slate-400">Pinjaman</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs {{ $overdueCount > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
                {{ $overdueCount > 0 ? "⚠️ {$overdueCount} pinjaman telah lewat jatuh tempo!" : "Semua pinjaman dalam jadwal aman" }}
            </div>
        </div>
    </div>

    <!-- 2. TABS NAVIGASI -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button wire:click="$set('activeTab', 'payable')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $activeTab === 'payable' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <span class="material-symbols-outlined text-[16px]">outbox</span>
            <span>Hutang Saya ({{ $debts->where('type', 'payable')->where('status', '!=', 'paid')->count() }})</span>
        </button>

        <button wire:click="$set('activeTab', 'receivable')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $activeTab === 'receivable' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <span class="material-symbols-outlined text-[16px]">move_to_inbox</span>
            <span>Piutang Saya ({{ $debts->where('type', 'receivable')->where('status', '!=', 'paid')->count() }})</span>
        </button>

        <button wire:click="$set('activeTab', 'paid')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $activeTab === 'paid' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            <span>Riwayat Lunas ({{ $debts->where('status', 'paid')->count() }})</span>
        </button>
    </div>

    <!-- 3. DEBTS LIST GRID -->
    @php
        $filteredDebts = $debts->filter(function ($d) use ($activeTab) {
            if ($activeTab === 'paid') return $d->status === 'paid';
            return $d->type === $activeTab && $d->status !== 'paid';
        });
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($filteredDebts as $d)
            @php
                $isPayable = $d->type === 'payable';
                $isPaid = $d->status === 'paid';
                $accentColor = $isPayable ? 'rose' : 'emerald';
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between hover:shadow-float transition-all group">
                <div>
                    <!-- Top Bar -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <span class="font-display font-bold text-base text-slate-900 dark:text-white block">
                                {{ $d->person_name }}
                            </span>
                            <span class="text-[11px] text-slate-400">
                                {{ $isPayable ? 'Hutang ke' : 'Dipinjam oleh' }} {{ $d->person_name }}
                            </span>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            @if($isPaid)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300">
                                    ✓ Lunas
                                </span>
                            @elseif($d->status === 'partial')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300">
                                    {{ $d->percentage }}% Dicicil
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300">
                                    Belum Dibayar
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Nominal Figures -->
                    <div class="my-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block uppercase font-medium">Sisa yang Belum Lunas</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-display font-extrabold text-xl sm:text-2xl {{ $isPayable ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                Rp {{ number_format($d->remaining_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2">
                            <span>Total Pokok:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Rp {{ number_format($d->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-0.5">
                            <span>Sudah Dibayar:</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($d->paid_amount, 0, ',', '.') }}</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2">
                            <div class="{{ $isPayable ? 'bg-rose-500' : 'bg-emerald-500' }} h-1.5 rounded-full" style="width: {{ $d->percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Due Date Indicator -->
                    <div class="flex items-center justify-between text-xs my-2">
                        <span class="text-slate-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">event</span>
                            <span>Tempo:</span>
                        </span>
                        @if($d->due_date)
                            @if($d->is_overdue)
                                <span class="font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">warning</span>
                                    <span>Lewat {{ abs($d->due_days_left) }} hari</span>
                                </span>
                            @else
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $d->due_date->translatedFormat('d M Y') }}
                                    @if($d->due_days_left !== null && $d->due_days_left <= 7 && !$isPaid)
                                        <span class="text-amber-600 font-bold">({{ $d->due_days_left }} hari lagi)</span>
                                    @endif
                                </span>
                            @endif
                        @else
                            <span class="text-slate-400">Tanpa Jatuh Tempo</span>
                        @endif
                    </div>

                    @if($d->description)
                        <p class="text-xs text-slate-500 dark:text-slate-400 italic line-clamp-2 mt-1">
                            "{{ $d->description }}"
                        </p>
                    @endif
                </div>

                <!-- Action Footer -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1">
                        <button wire:click="edit({{ $d->id }})" title="Edit" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-indigo-600 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button wire:confirm="Hapus catatan pinjaman ini beserta seluruh riwayat cicilannya?" wire:click="delete({{ $d->id }})" title="Hapus" class="p-1.5 rounded-xl hover:bg-rose-50 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>

                    <div>
                        @if(!$isPaid)
                            <button wire:click="openPaymentModal({{ $d->id }})" class="px-3.5 py-1.5 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">payments</span>
                                <span>{{ $isPayable ? 'Bayar Cicilan' : 'Terima Pembayaran' }}</span>
                            </button>
                        @else
                            <button wire:click="openPaymentModal({{ $d->id }})" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-200 transition-all">
                                Lihat Histori ({{ $d->payments->count() }})
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
                <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2 block">handshake</span>
                <p class="text-sm font-semibold">Belum ada catatan {{ $activeTab === 'payable' ? 'hutang' : ($activeTab === 'receivable' ? 'piutang' : 'pinjaman lunas') }}.</p>
                <button wire:click="openCreateModal('{{ $activeTab === 'paid' ? 'payable' : $activeTab }}')" class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    + Buat Catatan Baru
                </button>
            </div>
        @endforelse
    </div>

    <!-- 4. MODAL TAMBAH / EDIT PINJAMAN -->
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="relative max-w-lg w-full bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-float border border-slate-200 dark:border-slate-800 p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl {{ $type === 'payable' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">{{ $type === 'payable' ? 'outbox' : 'move_to_inbox' }}</span>
                        </div>
                        <div>
                            <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">
                                {{ $editingId ? 'Edit Catatan Pinjaman' : ($type === 'payable' ? 'Catat Hutang Baru' : 'Catat Piutang Baru') }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $type === 'payable' ? 'Uang yang Anda pinjam dan harus Anda kembalikan' : 'Uang yang orang lain pinjam dari Anda' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="resetForm" class="p-1 rounded-full hover:bg-slate-100 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <!-- Tipe Selector (Only when creating) -->
                    @if(!$editingId)
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tipe Pinjaman</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="$set('type', 'payable')" class="py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 {{ $type === 'payable' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                    <span>Hutang (Saya Pinjam)</span>
                                </button>
                                <button type="button" wire:click="$set('type', 'receivable')" class="py-2.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 {{ $type === 'receivable' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                    <span>Piutang (Orang Pinjam)</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Nama Pihak -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            {{ $type === 'payable' ? 'Nama Pemberi Pinjaman / Bank *' : 'Nama Peminjam *' }}
                        </label>
                        <input wire:model="person_name" type="text" placeholder="Contoh: BCA, Budi Santoso, Ibu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20" required>
                        <x-input-error :messages="$errors->get('person_name')" class="mt-1" />
                    </div>

                    <!-- Nominal Pokok -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal Pokok (Rp) *</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-slate-400">Rp</span>
                            <input wire:model="total_amount" type="number" step="any" placeholder="0" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-display font-bold text-sm focus:ring-2 focus:ring-indigo-500/20" required>
                        </div>
                        <x-input-error :messages="$errors->get('total_amount')" class="mt-1" />
                    </div>

                    <!-- Tanggal Jatuh Tempo & Dompet Terkait -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jatuh Tempo (Opsional)</label>
                            <input wire:model="due_date" type="date" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dompet Terkait</label>
                            <select wire:model="account_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                                <option value="">-- Tanpa Dompet --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Toggle affect wallet (Only on create) -->
                    @if(!$editingId)
                        <div class="p-3 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-800/40">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input wire:model="affect_wallet" type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">
                                        {{ $type === 'payable' ? 'Langsung masukkan uang pinjaman ke saldo dompet' : 'Langsung potong uang pinjaman dari saldo dompet' }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 block">
                                        {{ $type === 'payable' ? 'Saldo dompet terpilih akan otomatis bertambah sebesar nominal pokok.' : 'Saldo dompet terpilih akan otomatis berkurang karena dipinjamkan.' }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    @endif

                    <!-- Catatan / Deskripsi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keperluan</label>
                        <textarea wire:model="description" rows="2" placeholder="Tujuan pinjaman atau kesepakatan cicilan..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="resetForm" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-all">
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Pinjaman' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- 5. MODAL BAYAR CICILAN / HISTORI PELUNASAN -->
    @if($showPaymentModal && $selectedDebt)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="relative max-w-xl w-full bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-float border border-slate-200 dark:border-slate-800 p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider {{ $selectedDebt->type === 'payable' ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $selectedDebt->type === 'payable' ? 'Pelunasan Hutang' : 'Penerimaan Piutang' }}
                        </span>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">
                            {{ $selectedDebt->person_name }}
                        </h3>
                    </div>
                    <button wire:click="closePaymentModal" class="p-1 rounded-full hover:bg-slate-100 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Info Box -->
                <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400 block">Sisa Yang Harus Dibayar:</span>
                        <span class="font-display font-extrabold text-lg text-slate-900 dark:text-white">
                            Rp {{ number_format($selectedDebt->remaining_amount, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block">Total Pokok:</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">
                            Rp {{ number_format($selectedDebt->total_amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Form Catat Pembayaran Baru (Jika belum lunas) -->
                @if($selectedDebt->status !== 'paid')
                    <form wire:submit="recordPayment" class="space-y-3 pt-2">
                        <h4 class="font-display font-bold text-xs text-slate-800 dark:text-slate-200">Catat Pembayaran Baru</h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Nominal Pembayaran (Rp) *</label>
                                <input wire:model="payment_amount" type="number" step="any" max="{{ $selectedDebt->remaining_amount }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold" required>
                                <x-input-error :messages="$errors->get('payment_amount')" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Dompet Digunakan *</label>
                                <select wire:model="payment_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs" required>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('payment_account_id')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Tanggal Bayar *</label>
                                <input wire:model="payment_date" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Catatan Cicilan</label>
                                <input wire:model="payment_notes" type="text" placeholder="Misal: Cicilan ke-1 via transfer" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                            </div>
                        </div>

                        <div class="text-right pt-2">
                            <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition-all">
                                Konfirmasi &amp; Potong Saldo
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 font-semibold text-center">
                        🎉 Pinjaman ini telah lunas secara penuh!
                    </div>
                @endif

                <!-- Histori Cicilan / Pembayaran -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <h4 class="font-display font-bold text-xs text-slate-800 dark:text-slate-200">
                        Histori Pembayaran ({{ $selectedDebt->payments->count() }})
                    </h4>

                    <div class="max-h-44 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($selectedDebt->payments as $p)
                            <div class="py-2.5 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">
                                        Rp {{ number_format($p->amount, 0, ',', '.') }}
                                    </span>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $p->payment_date->translatedFormat('d M Y') }} • Dompet: {{ $p->account->name }}
                                        @if($p->notes)
                                            • <span class="italic">"{{ $p->notes }}"</span>
                                        @endif
                                    </div>
                                </div>
                                <button wire:confirm="Batalkan pembayaran ini? Saldo dompet akan dipulihkan." wire:click="deletePayment({{ $p->id }})" class="text-rose-500 hover:text-rose-700 text-xs font-semibold px-2 py-1 rounded hover:bg-rose-50">
                                    Batal
                                </button>
                            </div>
                        @empty
                            <div class="py-4 text-center text-slate-400 text-xs">Belum ada histori pembayaran.</div>
                        @endforelse
                    </div>
                </div>

                <div class="text-right pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button wire:click="closePaymentModal" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

