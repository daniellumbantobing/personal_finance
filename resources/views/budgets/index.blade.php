<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        showForm: false,
        isEdit: false,
        formAction: '{{ route('budgets.store') }}',
        category_id: '{{ $categories->first()->id ?? '' }}',
        amount: '',
        period: 'monthly',
        start_date: '{{ \Carbon\Carbon::now()->startOfMonth()->toDateString() }}',
        end_date: '{{ \Carbon\Carbon::now()->endOfMonth()->toDateString() }}',
        openCreate() {
            this.isEdit = false;
            this.formAction = '{{ route('budgets.store') }}';
            this.category_id = '{{ $categories->first()->id ?? '' }}';
            this.amount = '';
            this.period = 'monthly';
            this.start_date = '{{ \Carbon\Carbon::now()->startOfMonth()->toDateString() }}';
            this.end_date = '{{ \Carbon\Carbon::now()->endOfMonth()->toDateString() }}';
            this.showForm = true;
            $nextTick(() => {
                document.getElementById('budget-form-card')?.scrollIntoView({ behavior: 'smooth' });
            });
        },
        openEdit(b) {
            this.isEdit = true;
            this.formAction = '/budgets/' + b.id;
            this.category_id = b.category_id;
            this.amount = cleanNominal(b.amount);
            this.period = b.period;
            this.start_date = b.start_date ? b.start_date.substring(0, 10) : '{{ \Carbon\Carbon::now()->startOfMonth()->toDateString() }}';
            this.end_date = b.end_date ? b.end_date.substring(0, 10) : '{{ \Carbon\Carbon::now()->endOfMonth()->toDateString() }}';
            this.showForm = true;
            $nextTick(() => {
                document.getElementById('budget-form-card')?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    }">
        <!-- Header Banner & Period Pill -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">pie_chart</span>
                    <span>Disiplin Anggaran</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Anggaran Bulanan
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Pantau batas belanja per kategori agar keuangan tetap surplus dan terarah.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Periode: {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</span>
                </span>
                <button @click="openCreate()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    <span>Pasang Anggaran</span>
                </button>
            </div>
        </div>

        <!-- Summary Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Anggaran</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-slate-900 dark:text-white mt-3">Rp {{ number_format($totalBudgeted, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ count($budgets) }} Pos Anggaran Aktif</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terpakai Saat Ini</span>
                    <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">shopping_cart_checkout</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-rose-600 dark:text-rose-400 mt-3">Rp {{ number_format($totalSpent, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $overallPercentage }}% dari total limit anggaran</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Anggaran</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">savings</span>
                    </span>
                </div>
                @php
                    $remaining = max(0, $totalBudgeted - $totalSpent);
                @endphp
                <p class="font-display text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-3">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $totalSpent > $totalBudgeted ? 'Overbudget Rp ' . number_format($totalSpent - $totalBudgeted, 0, ',', '.') : 'Aman untuk bulan ini' }}</p>
            </div>
        </div>

        <!-- Form Card Section -->
        <div id="budget-form-card" x-show="showForm" style="display: none;" x-transition.duration.200ms class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Edit Batas Anggaran' : 'Pasang Anggaran Baru'"></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tentukan batas pengeluaran untuk kategori tertentu</p>
                </div>
                <button type="button" @click="showForm = false" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Batal</button>
            </div>

            <form :action="formAction" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Pengeluaran *</label>
                    <select name="category_id" x-model="category_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Batas Nominal (Rp) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                        <input type="text" inputmode="numeric" :value="formatRupiah(amount)" @input="amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(amount)" class="w-full pl-9 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="1.000.000" required />
                        <input type="hidden" name="amount" :value="amount">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                    <select name="period" x-model="period" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="monthly">Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="yearly">Tahunan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Dari Tanggal</label>
                    <input type="date" name="start_date" x-model="start_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" x-model="end_date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                </div>

                <div class="sm:col-span-2 lg:col-span-5 flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Anggaran'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Budget Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($budgets as $b)
                @php
                    $percentage = $b->amount > 0 ? min(100, round(($b->spent / $b->amount) * 100)) : 0;
                    $isOver = $b->spent > $b->amount;
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card flex flex-col justify-between hover:shadow-float transition-all">
                    <div>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white {{ $isOver ? 'bg-rose-600' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-indigo-600') }} shadow-sm">
                                    <span class="material-symbols-outlined text-[22px]">{{ $b->category->icon ?? 'category' }}</span>
                                </div>
                                <div>
                                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">{{ $b->category->name ?? 'Semua Pengeluaran' }}</h3>
                                    <span class="text-[11px] text-slate-400 capitalize">{{ $b->period }} ({{ $b->start_date->format('d M') }} - {{ $b->end_date->format('d M') }})</span>
                                </div>
                            </div>

                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $isOver ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : ($percentage >= 80 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300') }}">
                                {{ $percentage }}%
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-5 mb-3">
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full transition-all duration-500 {{ $isOver ? 'bg-rose-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-indigo-500') }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>

                        <!-- Amount Breakdown -->
                        <div class="flex items-center justify-between text-xs mt-2">
                            <div>
                                <span class="text-slate-400 block">Terpakai</span>
                                <span class="font-display font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($b->spent, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block">Batas Maksimal</span>
                                <span class="font-display font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($b->amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @if($isOver)
                            <div class="mt-3 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200/50 dark:border-rose-800/40 flex items-center gap-2 text-rose-600 dark:text-rose-400 text-xs">
                                <span class="material-symbols-outlined text-[16px]">warning</span>
                                <span>Melebihi batas Rp {{ number_format($b->spent - $b->amount, 0, ',', '.') }}!</span>
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 mt-5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <button @click="openEdit({{ $b->toJson() }})" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">edit</span>
                            <span>Ubah Limit</span>
                        </button>

                        <form action="{{ route('budgets.destroy', $b) }}" method="POST" onsubmit="return confirm('Hapus anggaran kategori {{ $b->category->name ?? '' }}?')">
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
                    <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600 mb-3">pie_chart</span>
                    <h3 class="font-display font-bold text-lg text-slate-800 dark:text-slate-200">Belum Ada Anggaran Aktif</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        Pasang batas belanja untuk kategori penting seperti Makanan, Hiburan, atau Belanja untuk mencegah pemborosan.
                    </p>
                    <button @click="openCreate()" class="mt-4 px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                        + Pasang Anggaran Pertama
                    </button>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

