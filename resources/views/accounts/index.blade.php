<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        showModal: false,
        isEdit: false,
        formAction: '{{ route('accounts.store') }}',
        name: '',
        type: 'cash',
        balance: '0',
        currency: '{{ auth()->user()->currency ?? 'IDR' }}',
        openCreate() {
            this.isEdit = false;
            this.formAction = '{{ route('accounts.store') }}';
            this.name = '';
            this.type = 'cash';
            this.balance = '0';
            this.currency = '{{ auth()->user()->currency ?? 'IDR' }}';
            this.showModal = true;
        },
        openEdit(acc) {
            this.isEdit = true;
            this.formAction = '/accounts/' + acc.id;
            this.name = acc.name;
            this.type = acc.type;
            this.balance = cleanNominal(acc.balance);
            this.currency = acc.currency || '{{ auth()->user()->currency ?? 'IDR' }}';
            this.showModal = true;
        }
    }">
        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">account_balance_wallet</span>
                    <span>Manajemen Finansial</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Dompet &amp; Rekening
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Kelola semua rekening bank, e-wallet, uang tunai, dan aset likuid Anda.
                </p>
            </div>

            <button @click="openCreate()" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Tambah Dompet</span>
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Saldo Terkonsolidasi</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-1.5">
                    <span class="text-sm text-slate-400 font-sans">Rp</span>
                    <span class="font-display text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ number_format($totalBalance, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Dari seluruh akun terdaftar</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jumlah Akun Terdaftar</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">credit_card</span>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="font-display text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ $accounts->count() }}</span>
                    <span class="text-sm text-slate-400 ml-1">Akun</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Bank, tunai, kartu, dan e-wallet</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Mata Uang Utama</span>
                    <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">currency_exchange</span>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="font-display text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ auth()->user()->currency ?? 'IDR' }}</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Rupiah Indonesia</p>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($accounts as $account)
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card hover:shadow-float transition-all relative group flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-md {{ match($account->type) {
                                    'bank' => 'bg-gradient-to-tr from-blue-600 to-indigo-600 shadow-blue-500/20',
                                    'ewallet' => 'bg-gradient-to-tr from-teal-500 to-emerald-600 shadow-teal-500/20',
                                    'credit_card' => 'bg-gradient-to-tr from-rose-500 to-pink-600 shadow-rose-500/20',
                                    'investment' => 'bg-gradient-to-tr from-amber-500 to-orange-600 shadow-amber-500/20',
                                    default => 'bg-gradient-to-tr from-slate-700 to-slate-900 shadow-slate-500/20'
                                } }}">
                                    <span class="material-symbols-outlined text-[24px]">
                                        {{ match($account->type) {
                                            'bank' => 'account_balance',
                                            'ewallet' => 'phone_android',
                                            'credit_card' => 'credit_card',
                                            'investment' => 'trending_up',
                                            default => 'wallet'
                                        } }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">{{ $account->name }}</h3>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ str_replace('_', ' ', $account->type) }}</span>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ $account->currency }}
                            </span>
                        </div>

                        <div class="my-5">
                            <span class="text-xs text-slate-400 font-medium block mb-0.5">Saldo Terkini</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-sm font-sans text-slate-400">Rp</span>
                                <span class="font-display text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ number_format($account->balance, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <button @click="openEdit({{ $account->toJson() }})" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">edit</span>
                            <span>Ubah</span>
                        </button>

                        <form action="{{ route('accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dompet {{ $account->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-rose-500 hover:text-rose-700 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-card">
                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">account_balance_wallet</span>
                    <h3 class="font-display font-bold text-lg text-slate-800 dark:text-slate-200">Belum Ada Dompet / Rekening</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        Mulai catat keuangan Anda dengan menambahkan dompet tunai atau rekening bank pertama.
                    </p>
                    <button @click="openCreate()" class="mt-4 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                        + Tambah Dompet Sekarang
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Modal Create / Edit Account -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition.opacity @click="showModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Ubah Akun / Dompet' : 'Tambah Akun Baru'"></h3>
                                <p class="text-xs text-slate-500">Lengkapi data informasi akun keuangan Anda.</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" class="space-y-4">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Dompet / Rekening <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="name" required placeholder="Contoh: BCA Prioritas, Mandiri, Dompet Tunai" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipe Akun <span class="text-rose-500">*</span></label>
                            <select name="type" x-model="type" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="cash">Tunai (Cash)</option>
                                <option value="bank">Rekening Bank</option>
                                <option value="ewallet">E-Wallet (GoPay, OVO, Dana, ShopeePay)</option>
                                <option value="credit_card">Kartu Kredit</option>
                                <option value="investment">Investasi / Reksadana / Saham</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Saldo <span class="text-rose-500">*</span></label>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" inputmode="numeric" :value="formatRupiah(balance)" @input="balance = cleanNominal($event.target.value); $event.target.value = formatRupiah(balance)" required class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="hidden" name="balance" :value="balance">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Mata Uang</label>
                            <input type="text" name="currency" x-model="currency" required maxlength="5" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95">
                                Simpan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

