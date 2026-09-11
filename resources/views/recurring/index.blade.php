<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        showForm: false,
        isEdit: false,
        formAction: '{{ route('recurring.store') }}',
        type: 'expense',
        amount: '',
        frequency: 'monthly',
        next_run_date: '{{ date('Y-m-d') }}',
        account_id: '{{ $accounts->first()->id ?? '' }}',
        category_id: '{{ $categories->first()->id ?? '' }}',
        description: '',
        is_active: true,

        openCreate() {
            this.isEdit = false;
            this.formAction = '{{ route('recurring.store') }}';
            this.type = 'expense';
            this.amount = '';
            this.frequency = 'monthly';
            this.next_run_date = '{{ date('Y-m-d') }}';
            this.account_id = '{{ $accounts->first()->id ?? '' }}';
            this.category_id = '{{ $categories->first()->id ?? '' }}';
            this.description = '';
            this.is_active = true;
            this.showForm = true;
            $nextTick(() => {
                document.getElementById('recurring-form-card')?.scrollIntoView({ behavior: 'smooth' });
            });
        },

        openEdit(r) {
            this.isEdit = true;
            this.formAction = '/recurring/' + r.id;
            this.type = r.type;
            this.amount = cleanNominal(r.amount);
            this.frequency = r.frequency;
            this.next_run_date = r.next_run_date ? r.next_run_date.substring(0, 10) : '{{ date('Y-m-d') }}';
            this.account_id = r.account_id;
            this.category_id = r.category_id || '';
            this.description = r.description;
            this.is_active = !!r.is_active;
            this.showForm = true;
            $nextTick(() => {
                document.getElementById('recurring-form-card')?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    }">
        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">event_repeat</span>
                    <span>Otomasi Jadwal &amp; Tagihan</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Transaksi Berulang (Recurring)
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Kelola langganan Netflix, Spotify, tagihan listrik, sewa, asuransi, dan gaji berkala.
                </p>
            </div>

            <button @click="showForm ? showForm = false : openCreate()" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]" x-text="showForm ? 'close' : 'add_circle'"></span>
                <span x-text="showForm ? 'Tutup Form' : 'Jadwal Rutin Baru'"></span>
            </button>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estimasi Tagihan / Bulan</span>
                    <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-rose-600 dark:text-rose-400 mt-3">Rp {{ number_format($activeMonthly, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">Total pengeluaran rutin aktif per bulan</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jadwal Aktif</span>
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">update</span>
                    </span>
                </div>
                <p class="font-display text-2xl font-black text-slate-900 dark:text-white mt-3">{{ $recurringTransactions->where('is_active', true)->count() }}</p>
                <p class="text-xs text-slate-400 mt-1">Dari {{ $recurringTransactions->count() }} total jadwal tercatat</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jatuh Tempo 7 Hari</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">alarm</span>
                    </span>
                </div>
                @php
                    $dueSoon = $recurringTransactions->where('is_active', true)->filter(fn($r) => $r->next_run_date && $r->next_run_date->diffInDays(now(), false) <= 0 && $r->next_run_date->diffInDays(now(), false) >= -7)->count();
                @endphp
                <p class="font-display text-2xl font-black text-amber-600 dark:text-amber-400 mt-3">{{ $dueSoon }}</p>
                <p class="text-xs text-slate-400 mt-1">Perlu perhatian minggu ini</p>
            </div>
        </div>

        <!-- Form Card Section -->
        <div id="recurring-form-card" x-show="showForm" style="display: none;" x-transition.duration.200ms class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Edit Jadwal Transaksi Rutin' : 'Pasang Jadwal Transaksi Rutin'"></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sistem akan mengingatkan Anda saat tanggal eksekusi tiba</p>
                </div>
                <button type="button" @click="showForm = false" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">Batal</button>
            </div>

            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Deskripsi -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Nama Tagihan *</label>
                        <input type="text" name="description" x-model="description" placeholder="Contoh: Langganan Spotify Premium, Listrik PLN" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <!-- Tipe -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipe Transaksi *</label>
                        <select name="type" x-model="type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="expense">Pengeluaran (Tagihan)</option>
                            <option value="income">Pemasukan (Gaji / Dividen)</option>
                        </select>
                    </div>

                    <!-- Nominal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal (Rp) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                            <input type="text" inputmode="numeric" :value="formatRupiah(amount)" @input="amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(amount)" required placeholder="100.000" class="w-full pl-9 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <input type="hidden" name="amount" :value="amount">
                        </div>
                    </div>

                    <!-- Frekuensi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Frekuensi Pengulangan *</label>
                        <select name="frequency" x-model="frequency" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="monthly">Bulanan</option>
                            <option value="weekly">Mingguan</option>
                            <option value="yearly">Tahunan</option>
                            <option value="daily">Harian</option>
                        </select>
                    </div>

                    <!-- Tanggal Eksekusi Berikutnya -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jatuh Tempo Berikutnya *</label>
                        <input type="date" name="next_run_date" x-model="next_run_date" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <!-- Dompet Sumber -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dompet / Rekening *</label>
                        <select name="account_id" x-model="account_id" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                        <select name="category_id" x-model="category_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_active_check" name="is_active" value="1" x-model="is_active" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <label for="is_active_check" class="text-xs font-medium text-slate-700 dark:text-slate-300">Aktifkan pengulangan ini secara otomatis</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all">
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Recurring Transactions List -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3.5 px-6">Nama Tagihan</th>
                            <th class="py-3.5 px-6">Frekuensi &amp; Akun</th>
                            <th class="py-3.5 px-6">Jatuh Tempo Berikutnya</th>
                            <th class="py-3.5 px-6 text-right">Nominal</th>
                            <th class="py-3.5 px-6 text-center">Status</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($recurringTransactions as $r)
                            @php
                                $daysLeft = $r->next_run_date ? (int) now()->startOfDay()->diffInDays($r->next_run_date->startOfDay(), false) : null;
                                $isDue = $daysLeft !== null && $daysLeft <= 0;
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- Nama Tagihan -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white flex-shrink-0 {{ $r->type === 'income' ? 'bg-emerald-500' : 'bg-rose-500' }} shadow-sm">
                                            <span class="material-symbols-outlined text-[18px]">
                                                {{ $r->type === 'income' ? 'arrow_downward' : 'event_repeat' }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $r->description }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $r->category->name ?? 'Tanpa Kategori' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Frekuensi & Akun -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 capitalize">{{ $r->frequency }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $r->account->name ?? '-' }}</div>
                                </td>

                                <!-- Jatuh Tempo -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="font-bold {{ $isDue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                                        {{ $r->next_run_date ? $r->next_run_date->format('d M Y') : '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        @if($daysLeft === 0)
                                            Hari ini!
                                        @elseif($daysLeft > 0)
                                            {{ $daysLeft }} hari lagi
                                        @else
                                            Terlewat {{ abs($daysLeft) }} hari
                                        @endif
                                    </div>
                                </td>

                                <!-- Nominal -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <span class="font-display font-black text-sm {{ $r->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $r->type === 'income' ? '+' : '-' }}Rp {{ number_format($r->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $r->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('recurring.process', $r) }}" method="POST" onsubmit="return confirm('Eksekusi transaksi rutin ini sekarang dan catat ke buku kas?')">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] flex items-center gap-1 transition-colors" title="Eksekusi & Catat Sekarang">
                                                <span class="material-symbols-outlined text-[14px]">play_arrow</span>
                                                <span>Proses</span>
                                            </button>
                                        </form>

                                        <button @click="openEdit({{ $r->toJson() }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>

                                        <form action="{{ route('recurring.destroy', $r) }}" method="POST" onsubmit="return confirm('Hapus jadwal transaksi rutin {{ $r->description }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Hapus">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">event_repeat</span>
                                    <p class="font-medium text-sm text-slate-600 dark:text-slate-400">Belum ada transaksi berulang yang didaftarkan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

