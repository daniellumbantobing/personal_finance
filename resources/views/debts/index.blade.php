<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        activeTab: 'payable',
        
        // Form Modal state
        showFormModal: false,
        isEdit: false,
        formAction: '{{ route('debts.store') }}',
        type: 'payable',
        person_name: '',
        total_amount: '',
        due_date: '{{ \Carbon\Carbon::now()->addMonth()->toDateString() }}',
        account_id: '{{ $accounts->first()->id ?? '' }}',
        description: '',
        affect_wallet: false,

        // Payment Modal state
        showPaymentModal: false,
        paymentFormAction: '',
        paymentPersonName: '',
        paymentType: 'payable',
        paymentRemaining: 0,
        paymentAmount: '',
        paymentAccountId: '{{ $accounts->first()->id ?? '' }}',
        paymentDate: '{{ date('Y-m-d') }}',
        paymentNotes: '',

        openCreate(type) {
            this.isEdit = false;
            this.formAction = '{{ route('debts.store') }}';
            this.type = type;
            this.person_name = '';
            this.total_amount = '';
            this.due_date = '{{ \Carbon\Carbon::now()->addMonth()->toDateString() }}';
            this.account_id = '{{ $accounts->first()->id ?? '' }}';
            this.description = '';
            this.affect_wallet = false;
            this.showFormModal = true;
        },

        openEdit(d) {
            this.isEdit = true;
            this.formAction = '/debts/' + d.id;
            this.type = d.type;
            this.person_name = d.person_name;
            this.total_amount = cleanNominal(d.total_amount);
            this.due_date = d.due_date ? d.due_date.substring(0, 10) : '';
            this.account_id = d.account_id || '{{ $accounts->first()->id ?? '' }}';
            this.description = d.description || '';
            this.affect_wallet = false;
            this.showFormModal = true;
        },

        openPayment(d) {
            this.paymentFormAction = '/debts/' + d.id + '/pay';
            this.paymentPersonName = d.person_name;
            this.paymentType = d.type;
            this.paymentRemaining = cleanNominal(d.remaining_amount);
            this.paymentAmount = cleanNominal(d.remaining_amount);
            this.paymentAccountId = '{{ $accounts->first()->id ?? '' }}';
            this.paymentDate = '{{ date('Y-m-d') }}';
            this.paymentNotes = '';
            this.showPaymentModal = true;
        }
    }">
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
                <button @click="openCreate('payable')" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-rose-600/20 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    <span>+ Catat Hutang</span>
                </button>
                <button @click="openCreate('receivable')" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-emerald-600/20 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    <span>+ Catat Piutang</span>
                </button>
            </div>
        </div>

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
            <button @click="activeTab = 'payable'" :class="activeTab === 'payable' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">outbox</span>
                <span>Hutang Saya ({{ $debts->where('type', 'payable')->where('status', '!=', 'paid')->count() }})</span>
            </button>
            <button @click="activeTab = 'receivable'" :class="activeTab === 'receivable' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">move_to_inbox</span>
                <span>Piutang Orang Lain ({{ $debts->where('type', 'receivable')->where('status', '!=', 'paid')->count() }})</span>
            </button>
            <button @click="activeTab = 'paid'" :class="activeTab === 'paid' ? 'bg-slate-800 text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                <span>Riwayat Lunas ({{ $debts->where('status', 'paid')->count() }})</span>
            </button>
        </div>

        <!-- 3. DEBTS CARDS LIST -->
        <div class="space-y-4">
            @php
                $filteredDebts = match($debts) {
                    default => $debts
                };
            @endphp

            @forelse($debts as $debt)
                <div x-show="(activeTab === 'payable' && '{{ $debt->type }}' === 'payable' && '{{ $debt->status }}' !== 'paid') || (activeTab === 'receivable' && '{{ $debt->type }}' === 'receivable' && '{{ $debt->status }}' !== 'paid') || (activeTab === 'paid' && '{{ $debt->status }}' === 'paid')" class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 border border-slate-200 dark:border-slate-800 shadow-card hover:shadow-float transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <!-- Identity & Details -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white flex-shrink-0 {{ $debt->type === 'payable' ? 'bg-rose-500 shadow-rose-500/20' : 'bg-emerald-500 shadow-emerald-500/20' }} shadow-md">
                                <span class="material-symbols-outlined text-[24px]">
                                    {{ $debt->type === 'payable' ? 'outbox' : 'move_to_inbox' }}
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-display font-extrabold text-lg text-slate-900 dark:text-white">{{ $debt->person_name }}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $debt->type === 'payable' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                                        {{ $debt->type === 'payable' ? 'Hutang Saya' : 'Piutang Anda' }}
                                    </span>
                                    @if($debt->status === 'paid')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Lunas</span>
                                    @elseif($debt->is_overdue)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 animate-pulse">Lewat Jatuh Tempo</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                    @if($debt->due_date)
                                        <span class="flex items-center gap-1 {{ $debt->is_overdue ? 'text-rose-600 font-bold' : '' }}">
                                            <span class="material-symbols-outlined text-[14px]">event</span>
                                            Jatuh Tempo: {{ $debt->due_date->format('d M Y') }}
                                            @if(!$debt->is_overdue && $debt->status !== 'paid' && $debt->due_days_left !== null)
                                                ({{ $debt->due_days_left }} hari lagi)
                                            @endif
                                        </span>
                                    @endif
                                    @if($debt->account)
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">account_balance_wallet</span>
                                            {{ $debt->account->name }}
                                        </span>
                                    @endif
                                    @if($debt->description)
                                        <span class="text-slate-400 italic">{{ $debt->description }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Amounts & Action Button -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 self-end md:self-center">
                            <div class="text-right">
                                <span class="text-[11px] text-slate-400 block">Sisa Belum Bayar</span>
                                <span class="font-display font-black text-xl {{ $debt->type === 'payable' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">Dari Total Rp {{ number_format($debt->total_amount, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($debt->status !== 'paid')
                                    <button @click="openPayment({{ $debt->toJson() }})" class="px-4 py-2 rounded-xl {{ $debt->type === 'payable' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20' }} text-white text-xs font-bold transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px]">payments</span>
                                        <span>{{ $debt->type === 'payable' ? 'Bayar Cicilan' : 'Terima Cicilan' }}</span>
                                    </button>
                                @endif

                                <button @click="openEdit({{ $debt->toJson() }})" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs" title="Edit">
                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                </button>

                                <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('Hapus catatan pinjaman ini beserta seluruh histori pembayarannya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 text-xs" title="Hapus">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                            <span>Pelunasan: {{ $debt->percentage }}%</span>
                            <span>Sudah Dibayar: <strong class="text-slate-900 dark:text-white">Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}</strong></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $debt->type === 'payable' ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $debt->percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Payment Installment Records -->
                    @if($debt->payments->isNotEmpty())
                        <div class="mt-4 pt-3 border-t border-dashed border-slate-200 dark:border-slate-800" x-data="{ showHistory: false }">
                            <button @click="showHistory = !showHistory" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]" x-text="showHistory ? 'expand_less' : 'history'"></span>
                                <span x-text="showHistory ? 'Sembunyikan Histori Cicilan (' + {{ $debt->payments->count() }} + ')' : 'Lihat ' + {{ $debt->payments->count() }} + ' Histori Pembayaran Cicilan'"></span>
                            </button>

                            <div x-show="showHistory" style="display: none;" class="mt-3 space-y-2">
                                @foreach($debt->payments as $payment)
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span>
                                            <div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '-' }}</span>
                                                <span class="text-slate-400 text-[11px] ml-1">({{ $payment->account->name ?? 'Kas' }})</span>
                                                @if($payment->notes)
                                                    <span class="text-slate-400 italic text-[11px] ml-1">• {{ $payment->notes }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="font-display font-bold text-slate-900 dark:text-white">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                            <form action="{{ route('debts.payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Batalkan pembayaran cicilan ini? Saldo dompet akan disesuaikan kembali.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-[11px] font-semibold" title="Batalkan & Pulihkan Saldo">
                                                    Batal
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="py-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-card">
                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">handshake</span>
                    <h3 class="font-display font-bold text-lg text-slate-800 dark:text-slate-200">Belum Ada Catatan Hutang / Piutang</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        Catat pinjaman uang yang harus Anda bayar atau piutang yang perlu Anda tagih.
                    </p>
                </div>
            @endforelse
        </div>

        <!-- 4. MODAL CREATE / EDIT DEBT -->
        <div x-show="showFormModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showFormModal" x-transition.opacity @click="showFormModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showFormModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white" :class="type === 'payable' ? 'bg-rose-600' : 'bg-emerald-600'">
                                <span class="material-symbols-outlined text-[20px]" x-text="type === 'payable' ? 'outbox' : 'move_to_inbox'"></span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Ubah Catatan Pinjaman' : (type === 'payable' ? 'Catat Hutang Baru' : 'Catat Piutang Baru')"></h3>
                                <p class="text-xs text-slate-500">Lengkapi data nominal dan jatuh tempo pembayaran.</p>
                            </div>
                        </div>
                        <button @click="showFormModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" class="space-y-4">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <!-- Tipe Selector Pill Tabs -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jenis Transaksi *</label>
                            <input type="hidden" name="type" :value="type">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="type = 'payable'" :class="type === 'payable' ? 'bg-rose-600 text-white font-bold shadow-md shadow-rose-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium'" class="py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                                    <span class="material-symbols-outlined text-[16px]">outbox</span>
                                    <span>Hutang (Kewajiban)</span>
                                </button>
                                <button type="button" @click="type = 'receivable'" :class="type === 'receivable' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium'" class="py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                                    <span class="material-symbols-outlined text-[16px]">move_to_inbox</span>
                                    <span>Piutang (Hak Tagih)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Nama Orang / Pihak -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" x-text="type === 'payable' ? 'Pemberi Pinjaman (Nama Orang / Bank) *' : 'Peminjam (Nama Orang yang Berhutang) *'"></label>
                            <input type="text" name="person_name" x-model="person_name" required placeholder="Contoh: Budi Santoso, Bank BCA" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Nominal Total -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Total Nominal Pinjaman (Rp) *</label>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="hidden" name="total_amount" :value="total_amount">
                                <input type="text" inputmode="numeric" :value="formatRupiah(total_amount)" @input="total_amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(total_amount)" required placeholder="5.000.000" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-semibold text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Jatuh Tempo & Dompet -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jatuh Tempo</label>
                                <input type="date" name="due_date" x-model="due_date" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Terkait Dompet / Akun</label>
                                <select name="account_id" x-model="account_id" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Tanpa Akun Tertentu --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Catatan / Deskripsi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea name="description" x-model="description" rows="2" placeholder="Keperluan dana, kontak, nomor referensi..." class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <!-- Affect Wallet Checkbox (Only when creating) -->
                        <template x-if="!isEdit">
                            <div class="p-3 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/60">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="affect_wallet" value="1" x-model="affect_wallet" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Sesuaikan Saldo Dompet Sekarang</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5 leading-snug">
                                            Jika dicentang, saldo dompet terpilih akan otomatis bertambah (jika hutang cair) atau berkurang (jika Anda meminjamkan uang) di mutasi kas.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </template>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showFormModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95">
                                Simpan Data Pinjaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 5. MODAL RECORD PAYMENT (BAYAR CICILAN) -->
        <div x-show="showPaymentModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showPaymentModal" x-transition.opacity @click="showPaymentModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showPaymentModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white" :class="paymentType === 'payable' ? 'bg-rose-600' : 'bg-emerald-600'">
                                <span class="material-symbols-outlined text-[20px]">payments</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="paymentType === 'payable' ? 'Bayar Cicilan Hutang' : 'Terima Pelunasan Piutang'"></h3>
                                <p class="text-xs text-slate-500" x-text="'Pihak: ' + paymentPersonName"></p>
                            </div>
                        </div>
                        <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="paymentFormAction" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" x-text="paymentType === 'payable' ? 'Bayar Menggunakan Dompet *' : 'Uang Masuk ke Dompet *'"></label>
                            <select name="payment_account_id" x-model="paymentAccountId" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Nominal Pembayaran (Rp) *</label>
                                <span class="text-[11px] text-slate-400">Sisa: Rp <span x-text="formatRupiah(paymentRemaining)"></span></span>
                            </div>
                            <div class="relative rounded-2xl">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="hidden" name="payment_amount" :value="paymentAmount">
                                <input type="text" inputmode="numeric" :value="formatRupiah(paymentAmount)" @input="paymentAmount = cleanNominal($event.target.value); $event.target.value = formatRupiah(paymentAmount)" required placeholder="0" class="w-full pl-10 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Bayar *</label>
                            <input type="date" name="payment_date" x-model="paymentDate" required class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Cicilan (Opsional)</label>
                            <input type="text" name="payment_notes" x-model="paymentNotes" placeholder="Cicilan ke-1, transfer lewat BCA, dll." class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showPaymentModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-white text-xs font-bold shadow-md active:scale-95 transition-all" :class="paymentType === 'payable' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20'">
                                Simpan Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

