<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        showGoalModal: false,
        isEdit: false,
        goalFormAction: '{{ route('saving-goals.store') }}',
        name: '',
        target_amount: '',
        current_amount: '0',
        target_date: '',
        description: '',

        // Quick Deposit Modal
        showDepositModal: false,
        depositFormAction: '',
        depositGoalName: '',
        depositAmount: '',
        depositAccountId: '{{ $accounts->first()->id ?? '' }}',

        openCreate() {
            this.isEdit = false;
            this.goalFormAction = '{{ route('saving-goals.store') }}';
            this.name = '';
            this.target_amount = '';
            this.current_amount = '0';
            this.target_date = '';
            this.description = '';
            this.showGoalModal = true;
        },

        openEdit(g) {
            this.isEdit = true;
            this.goalFormAction = '/saving-goals/' + g.id;
            this.name = g.name;
            this.target_amount = cleanNominal(g.target_amount);
            this.current_amount = cleanNominal(g.current_amount);
            this.target_date = g.target_date ? g.target_date.substring(0, 10) : '';
            this.description = g.description || '';
            this.showGoalModal = true;
        },

        openDeposit(g) {
            this.depositFormAction = '/saving-goals/' + g.id + '/deposit';
            this.depositGoalName = g.name;
            this.depositAmount = '';
            this.depositAccountId = '{{ $accounts->first()->id ?? '' }}';
            this.showDepositModal = true;
        }
    }">
        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">savings</span>
                    <span>Impian &amp; Tabungan Masa Depan</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Target Tabungan
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Wujudkan rencana keuangan dengan menabung secara berkala ke pos tujuan Anda.
                </p>
            </div>

            <button @click="openCreate()" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Buat Target Baru</span>
            </button>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Target Tabungan</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">flag</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-slate-900 dark:text-white mt-3">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ count($goals) }} Rencana Terdaftar</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Dana Terkumpul</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">savings</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-3">Rp {{ number_format($totalSaved, 0, ',', '.') }}</p>
                @php
                    $overallSavedPct = $totalTarget > 0 ? min(100, round(($totalSaved / $totalTarget) * 100)) : 0;
                @endphp
                <p class="text-xs text-slate-400 mt-1">{{ $overallSavedPct }}% dari keseluruhan target</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kekurangan Dana</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">trending_up</span>
                    </span>
                </div>
                @php
                    $shortfall = max(0, $totalTarget - $totalSaved);
                @endphp
                <p class="font-display text-2xl font-black text-amber-600 dark:text-amber-400 mt-3">Rp {{ number_format($shortfall, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">Sisa yang perlu disisihkan</p>
            </div>
        </div>

        <!-- Goals Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($goals as $g)
                @php
                    $percentage = $g->target_amount > 0 ? min(100, round(($g->current_amount / $g->target_amount) * 100)) : 0;
                    $isCompleted = $g->status === 'completed' || $g->current_amount >= $g->target_amount;
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card hover:shadow-float transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white {{ $isCompleted ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-indigo-600 shadow-indigo-500/20' }} shadow-md">
                                    <span class="material-symbols-outlined text-[24px]">
                                        {{ $isCompleted ? 'task_alt' : 'savings' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">{{ $g->name }}</h3>
                                    <span class="text-[11px] text-slate-400">
                                        @if($g->target_date)
                                            Target: {{ $g->target_date->format('d M Y') }}
                                        @else
                                            Tanpa batas waktu
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $isCompleted ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300' : 'bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300' }}">
                                {{ $percentage }}%
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-5 mb-3">
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full transition-all duration-500 {{ $isCompleted ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>

                        <!-- Amount Breakdown -->
                        <div class="flex items-center justify-between text-xs mt-2">
                            <div>
                                <span class="text-slate-400 block">Terkumpul</span>
                                <span class="font-display font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($g->current_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block">Target Sasaran</span>
                                <span class="font-display font-bold text-slate-900 dark:text-white">Rp {{ number_format($g->target_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @if($g->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 italic line-clamp-2">{{ $g->description }}</p>
                        @endif
                    </div>

                    <div class="pt-4 mt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        @if(!$isCompleted)
                            <button @click="openDeposit({{ $g->toJson() }})" class="w-full py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>Setor Tabungan</span>
                            </button>
                        @else
                            <div class="py-2 text-center text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                <span>Target Berhasil Dicapai! 🎉</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-xs pt-1">
                            <button @click="openEdit({{ $g->toJson() }})" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Ubah</span>
                            </button>

                            <form action="{{ route('saving-goals.destroy', $g) }}" method="POST" onsubmit="return confirm('Hapus target tabungan {{ $g->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-rose-500 hover:text-rose-700 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-card">
                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">flag</span>
                    <h3 class="font-display font-bold text-lg text-slate-800 dark:text-slate-200">Belum Ada Target Tabungan</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        Pasang target untuk Dana Darurat, Liburan, Gadget Baru, atau Investasi Rumah.
                    </p>
                    <button @click="openCreate()" class="mt-4 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                        + Buat Rencana Tabungan
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Modal Create / Edit Goal -->
        <div x-show="showGoalModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showGoalModal" x-transition.opacity @click="showGoalModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showGoalModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">savings</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Ubah Rencana Tabungan' : 'Buat Target Tabungan Baru'"></h3>
                                <p class="text-xs text-slate-500">Tentukan nama dan nominal impian Anda.</p>
                            </div>
                        </div>
                        <button @click="showGoalModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="goalFormAction" method="POST" class="space-y-4">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Impian / Sasaran *</label>
                            <input type="text" name="name" x-model="name" required placeholder="Contoh: Dana Darurat 6 Bulan, Liburan ke Jepang" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Nominal (Rp) *</label>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" inputmode="numeric" :value="formatRupiah(target_amount)" @input="target_amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(target_amount)" required placeholder="10.000.000" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="hidden" name="target_amount" :value="target_amount">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dana Terkumpul Awal (Rp)</label>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" inputmode="numeric" :value="formatRupiah(current_amount)" @input="current_amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(current_amount)" placeholder="0" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="hidden" name="current_amount" :value="current_amount">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Tanggal Pencapaian</label>
                            <input type="date" name="target_date" x-model="target_date" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Catatan Tambahan</label>
                            <textarea name="description" x-model="description" rows="2" placeholder="Catatan motivasi atau peruntukan target..." class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showGoalModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95">
                                Simpan Target
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Setor Tabungan (Quick Deposit) -->
        <div x-show="showDepositModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDepositModal" x-transition.opacity @click="showDepositModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDepositModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Setor Tabungan</h3>
                                <p class="text-xs text-slate-500" x-text="'Target: ' + depositGoalName"></p>
                            </div>
                        </div>
                        <button @click="showDepositModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="depositFormAction" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pilih Dompet Sumber Dana *</label>
                            <select name="account_id" x-model="depositAccountId" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">Saldo dompet ini akan dipotong otomatis dan dicatat di transaksi.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal Setoran (Rp) *</label>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" inputmode="numeric" :value="formatRupiah(depositAmount)" @input="depositAmount = cleanNominal($event.target.value); $event.target.value = formatRupiah(depositAmount)" required placeholder="500.000" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="hidden" name="amount" :value="depositAmount">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showDepositModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 active:scale-95">
                                Setor Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

