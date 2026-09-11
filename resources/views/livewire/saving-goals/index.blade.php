<?php

use Livewire\Volt\Component;
use App\Models\SavingGoal;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $goals = [];
    public $accounts = [];

    public $name = '';
    public $target_amount = '';
    public $current_amount = 0;
    public $target_date = '';
    public $description = '';
    public $editingId = null;

    // Quick deposit state
    public $depositGoalId = null;
    public $depositAmount = '';
    public $depositAccountId = '';

    public $totalTarget = 0;
    public $totalSaved = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $this->accounts = $user->accounts()->orderBy('name')->get();
        if ($this->accounts->isNotEmpty()) {
            $this->depositAccountId = $this->accounts->first()->id;
        }

        $this->goals = $user->savingGoals()
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('target_date', 'asc')
            ->get();

        $this->totalTarget = $this->goals->where('status', 'active')->sum('target_amount');
        $this->totalSaved = $this->goals->where('status', 'active')->sum('current_amount');
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date' => 'nullable|date|after_or_equal:today',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['current_amount'] = $validated['current_amount'] ?: 0;

        if ($this->editingId) {
            $goal = Auth::user()->savingGoals()->findOrFail($this->editingId);
            $status = $validated['current_amount'] >= $validated['target_amount'] ? 'completed' : $goal->status;
            $goal->update(array_merge($validated, ['status' => $status]));
        } else {
            $status = $validated['current_amount'] >= $validated['target_amount'] ? 'completed' : 'active';
            Auth::user()->savingGoals()->create(array_merge($validated, ['status' => $status]));
        }

        $this->cancelEdit();
        $this->loadData();
    }

    public function edit($id)
    {
        $goal = Auth::user()->savingGoals()->findOrFail($id);
        $this->editingId = $goal->id;
        $this->name = $goal->name;
        $this->target_amount = $goal->target_amount;
        $this->current_amount = $goal->current_amount;
        $this->target_date = $goal->target_date ? $goal->target_date->toDateString() : '';
        $this->description = $goal->description ?? '';
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'target_amount', 'current_amount', 'target_date', 'description', 'editingId']);
    }

    public function delete($id)
    {
        Auth::user()->savingGoals()->findOrFail($id)->delete();
        $this->loadData();
    }

    public function openDepositModal($id)
    {
        $this->depositGoalId = $id;
        $this->depositAmount = '';
    }

    public function makeDeposit()
    {
        $this->validate([
            'depositAmount' => 'required|numeric|min:1000',
            'depositAccountId' => 'required|exists:accounts,id',
        ]);

        $goal = Auth::user()->savingGoals()->findOrFail($this->depositGoalId);
        $account = Auth::user()->accounts()->findOrFail($this->depositAccountId);

        // Deduct from account & record transaction
        $account->decrement('balance', $this->depositAmount);
        
        Transaction::create([
            'user_id' => Auth::id(),
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => $this->depositAmount,
            'transaction_date' => Carbon::now()->toDateString(),
            'description' => 'Setor Tabungan: ' . $goal->name,
        ]);

        // Increment goal
        $newAmount = $goal->current_amount + $this->depositAmount;
        $status = $newAmount >= $goal->target_amount ? 'completed' : $goal->status;
        
        $goal->update([
            'current_amount' => $newAmount,
            'status' => $status,
        ]);

        $this->depositGoalId = null;
        $this->depositAmount = '';
        $this->loadData();
    }
}; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-2xl text-slate-900 dark:text-white tracking-tight">Target Tabungan (Saving Goals)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Wujudkan impian finansial Anda dengan target tabungan terukur</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                {{ $goals->where('status', 'completed')->count() }} Target Berhasil
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Sasaran Dana</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">flag</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-slate-900 dark:text-white mt-2">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $goals->where('status', 'active')->count() }} Target Sedang Berjalan</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sudah Terkumpul</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
            </div>
            <p class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">Rp {{ number_format($totalSaved, 0, ',', '.') }}</p>
            @php
                $pctTotal = $totalTarget > 0 ? min(100, round(($totalSaved / $totalTarget) * 100)) : 0;
            @endphp
            <p class="text-xs text-slate-400 mt-1">{{ $pctTotal }}% dari total target terkumpul</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Kebutuhan</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">hourglass_top</span>
                </span>
            </div>
            @php
                $needed = max(0, $totalTarget - $totalSaved);
            @endphp
            <p class="font-display text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">Rp {{ number_format($needed, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Perlu ditabung untuk mencapai 100%</p>
        </div>
    </div>

    <!-- Form Section -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                    {{ $editingId ? 'Edit Target Tabungan' : 'Buat Target Tabungan Baru' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tentukan nama impian, target nominal, dan tenggat waktu pencapaian</p>
            </div>
            @if($editingId)
                <button wire:click="cancelEdit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 underline">Batal</button>
            @endif
        </div>

        <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Target</label>
                <input type="text" wire:model="name" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Beli Laptop Baru, Liburan Jepang" required />
                @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Nominal (Rp)</label>
                <input type="number" step="1000" wire:model="target_amount" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: 15000000" required />
                @error('target_amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Saldo Awal Terkumpul</label>
                <input type="number" step="1000" wire:model="current_amount" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="0" />
                @error('current_amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Tanggal (Deadline)</label>
                <input type="date" wire:model="target_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                @error('target_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Motivasi</label>
                <input type="text" wire:model="description" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Menabung 500rb per minggu dari pos gaji" />
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-sm transition-all shadow-md active:scale-95 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] mr-1">check</span>
                    <span>Simpan Target</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Goals Grid Cards -->
    <div class="space-y-4">
        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Daftar Impian & Target</h2>

        @if(count($goals) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($goals as $g)
                    @php
                        $pct = $g->percentage;
                        $isCompleted = $g->status === 'completed' || $g->current_amount >= $g->target_amount;
                        $daysLeft = $g->target_date ? Carbon::now()->diffInDays($g->target_date, false) : null;
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card flex flex-col justify-between hover:border-indigo-200 dark:hover:border-slate-700 transition-all relative overflow-hidden">
                        @if($isCompleted)
                            <div class="absolute -right-8 -top-8 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
                        @endif

                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl {{ $isCompleted ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30' : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' }} flex items-center justify-center transition-all">
                                        <span class="material-symbols-outlined text-[22px]">{{ $isCompleted ? 'emoji_events' : 'flag' }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white leading-tight">{{ $g->name }}</h3>
                                        @if($g->target_date)
                                            <span class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                                <span class="material-symbols-outlined text-[12px]">event</span>
                                                Tenggat: {{ $g->target_date->translatedFormat('d M Y') }}
                                                @if(!$isCompleted)
                                                    @if($daysLeft > 0)
                                                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold">({{ $daysLeft }} hari lagi)</span>
                                                    @elseif($daysLeft === 0)
                                                        <span class="text-amber-600 font-semibold">(Hari ini)</span>
                                                    @else
                                                        <span class="text-rose-600 font-semibold">({{ abs($daysLeft) }} hari lewat)</span>
                                                    @endif
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($isCompleted)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300/40">
                                        🎉 Tercapai!
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50">
                                        {{ $pct }}%
                                    </span>
                                @endif
                            </div>

                            @if($g->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 italic">"{{ $g->description }}"</p>
                            @endif

                            <!-- Visual Progress Bar -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-baseline justify-between text-xs">
                                    <span class="font-bold text-slate-900 dark:text-white">Rp {{ number_format($g->current_amount, 0, ',', '.') }}</span>
                                    <span class="text-slate-400">Target: Rp {{ number_format($g->target_amount, 0, ',', '.') }}</span>
                                </div>

                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5">
                                    <div class="{{ $isCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-indigo-600' }} h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span>{{ $pct }}% tercapai</span>
                                    <span>Sisa: Rp {{ number_format(max(0, $g->target_amount - $g->current_amount), 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            @if(!$isCompleted)
                                <button wire:click="openDepositModal({{ $g->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 font-bold text-xs transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                    <span>Setor Dana</span>
                                </button>
                            @else
                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    Target Terpenuhi
                                </span>
                            @endif

                            <div class="flex items-center gap-1">
                                <button wire:click="edit({{ $g->id }})" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                <button wire:confirm="Hapus target tabungan ini?" wire:click="delete({{ $g->id }})" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-12 border border-slate-200/80 dark:border-slate-800 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-[32px]">flag</span>
                </div>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Belum Ada Target Tabungan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">Mulai rencanakan pembelian barang, dana darurat, atau liburan Anda sekarang.</p>
            </div>
        @endif
    </div>

    <!-- Quick Deposit Modal -->
    @if($depositGoalId)
        @php
            $activeGoal = $goals->firstWhere('id', $depositGoalId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-7 border border-slate-200/80 dark:border-slate-800 shadow-float relative animate-in fade-in zoom-in duration-150">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Setor ke Tabungan</h3>
                    </div>
                    <button wire:click="$set('depositGoalId', null)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                    Menambahkan saldo ke target: <span class="font-bold text-slate-800 dark:text-white">{{ $activeGoal?->name }}</span>. Saldo rekening yang dipilih akan otomatis dipotong.
                </p>

                <form wire:submit="makeDeposit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Ambil dari Dompet / Rekening</label>
                        <select wire:model="depositAccountId" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                        @error('depositAccountId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal Setoran (Rp)</label>
                        <input type="number" step="1000" wire:model="depositAmount" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: 250000" required />
                        @error('depositAmount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('depositGoalId', null)" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold text-xs">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-xs transition-all shadow-md active:scale-95">
                            Konfirmasi Setor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

