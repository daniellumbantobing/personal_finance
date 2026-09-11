<x-app-layout>
    <div class="space-y-8 pb-12" x-data="transactionsManager()">
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
                <button @click="openImportModal()" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">upload_file</span>
                    <span>Import CSV</span>
                </button>

                @php
                    $exportParams = array_filter([
                        'search' => request('search'),
                        'type' => request('type'),
                        'account_id' => request('account_id'),
                        'category_id' => request('category_id'),
                        'period' => request('period'),
                        'date_from' => request('date_from'),
                        'date_to' => request('date_to'),
                    ]);
                @endphp
                <a href="{{ route('transactions.export.csv', $exportParams) }}" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    <span>Export CSV</span>
                </a>

                <a href="{{ route('transactions.export.print', $exportParams) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">print</span>
                    <span>Cetak / PDF</span>
                </a>

                <button @click="showForm ? showForm = false : openCreate()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-indigo-500/20 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]" x-text="showForm ? 'close' : 'add'"></span>
                    <span x-text="showForm ? 'Tutup Form' : 'Tambah Transaksi'"></span>
                </button>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pemasukan</span>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-sm font-sans text-emerald-500">Rp</span>
                    <span class="font-display text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ number_format($summaryIncome, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Berdasarkan filter aktif</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pengeluaran</span>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-sm font-sans text-rose-500">Rp</span>
                    <span class="font-display text-2xl font-black text-rose-600 dark:text-rose-400">
                        {{ number_format($summaryExpense, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Berdasarkan filter aktif</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Selisih Bersih (Net)</span>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-sm font-sans {{ $summaryNet >= 0 ? 'text-indigo-500' : 'text-rose-500' }}">Rp</span>
                    <span class="font-display text-2xl font-black {{ $summaryNet >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ number_format($summaryNet, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ $summaryNet >= 0 ? 'Surplus anggaran' : 'Defisit anggaran' }}</p>
            </div>
        </div>

        <!-- 1. FORM CREATE / EDIT TRANSAKSI -->
        <div id="transaction-form-card" x-show="showForm" style="display: none;" x-transition.duration.200ms class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]" x-text="isEdit ? 'edit_note' : 'add_circle'"></span>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Edit Data Transaksi' : 'Catat Transaksi Baru'"></h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan saldo akun dan kategori terisi dengan benar</p>
                    </div>
                </div>
                <button type="button" @click="showForm = false" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-white">Batal</button>
            </div>

            <form :action="formAction" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Tipe Selector Pill Tabs -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Tipe Transaksi</label>
                    <input type="hidden" name="type" :value="type">
                    <div class="grid grid-cols-3 gap-2 max-w-md">
                        <button type="button" @click="type = 'expense'" :class="type === 'expense' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium'" class="py-2.5 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">arrow_outward</span>
                            <span>Pengeluaran</span>
                        </button>
                        <button type="button" @click="type = 'income'" :class="type === 'income' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium'" class="py-2.5 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                            <span>Pemasukan</span>
                        </button>
                        <button type="button" @click="type = 'transfer'" :class="type === 'transfer' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium'" class="py-2.5 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
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
                            <input type="text" inputmode="numeric" :value="formatRupiah(amount)" @input="amount = cleanNominal($event.target.value); $event.target.value = formatRupiah(amount)" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-display font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="0" required>
                            <input type="hidden" name="amount" :value="amount">
                        </div>
                    </div>

                    <!-- Tanggal Transaksi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Transaksi *</label>
                        <input type="date" name="transaction_date" x-model="transaction_date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                    </div>

                    <!-- Dompet Asal -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1" x-text="type === 'transfer' ? 'Dompet Sumber Dana *' : 'Dompet / Rekening *'"></label>
                        <select name="account_id" x-model="account_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                            <option value="">-- Pilih Dompet --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dompet Tujuan (Khusus Transfer) -->
                    <div x-show="type === 'transfer'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dompet Tujuan Transfer *</label>
                        <select name="destination_account_id" x-model="destination_account_id" :required="type === 'transfer'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">-- Pilih Dompet Tujuan --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kategori (Income / Expense) -->
                    <div x-show="type !== 'transfer'">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori *</label>
                        <select name="category_id" x-model="category_id" :required="type !== 'transfer'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }} ({{ ucfirst($cat->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Deskripsi Singkat -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Transaksi *</label>
                        <input type="text" name="description" x-model="description" placeholder="Contoh: Belanja Bulanan di Supermarket" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" x-model="notes" placeholder="Nomor invoice, keterangan promo, atau rincian item" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    <!-- Lampiran Struk / Bukti Transfer -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Foto Struk / Bukti Pembayaran (JPG, PNG, WEBP, PDF maks 2MB)
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="file" name="attachment" accept="image/*,.pdf" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                            <template x-if="existingAttachment">
                                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                    <img :src="existingAttachment" class="w-10 h-10 rounded-lg object-cover">
                                    <span class="text-[11px] text-slate-500">Struk tersimpan</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showForm = false" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all">
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Catat Transaksi Sekarang'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. ADVANCED MULTI-FILTER TOOLBAR -->
        <form method="GET" action="{{ route('transactions.index') }}" class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-card space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-indigo-600 dark:text-indigo-400">filter_alt</span>
                    <span class="font-display font-bold text-sm text-slate-900 dark:text-white">Filter &amp; Pencarian</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        {{ $transactions->count() }} Ditemukan
                    </span>
                </div>
                <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">restart_alt</span>
                    <span>Reset Filter</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Text -->
                <div class="relative lg:col-span-1">
                    <span class="material-symbols-outlined text-[18px] absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi / catatan..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                <!-- Filter Type -->
                <div>
                    <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all">Semua Tipe</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Pemasukan (+)</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran (-)</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer (⇄)</option>
                    </select>
                </div>

                <!-- Filter Account -->
                <div>
                    <select name="account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all">Semua Dompet</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Category -->
                <div>
                    <select name="category_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Period Preset -->
                <div>
                    <select name="period" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/20">
                        <option value="this_month" {{ request('period', 'this_month') === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                        <option value="last_30_days" {{ request('period') === 'last_30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                        <option value="all" {{ request('period') === 'all' ? 'selected' : '' }}>Semua Riwayat</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs transition-all">
                    Terapkan Filter
                </button>
            </div>
        </form>

        <!-- 3. TRANSACTIONS TABLE LIST -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3.5 px-6">Tanggal</th>
                            <th class="py-3.5 px-6">Deskripsi &amp; Dompet</th>
                            <th class="py-3.5 px-6">Kategori</th>
                            <th class="py-3.5 px-6 text-right">Nominal</th>
                            <th class="py-3.5 px-6 text-center">Struk</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($transactions as $tx)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- Tanggal -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $tx->transaction_date->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $tx->transaction_date->translatedFormat('l') }}</div>
                                </td>

                                <!-- Deskripsi & Dompet -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white flex-shrink-0 {{ match($tx->type) {
                                            'income' => 'bg-emerald-500 shadow-sm shadow-emerald-500/20',
                                            'expense' => 'bg-rose-500 shadow-sm shadow-rose-500/20',
                                            'transfer' => 'bg-indigo-500 shadow-sm shadow-indigo-500/20',
                                            default => 'bg-slate-600'
                                        } }}">
                                            <span class="material-symbols-outlined text-[18px]">
                                                {{ match($tx->type) {
                                                    'income' => 'arrow_downward',
                                                    'expense' => 'arrow_outward',
                                                    'transfer' => 'sync_alt',
                                                    default => 'receipt'
                                                } }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $tx->description }}</div>
                                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                <span>{{ $tx->account->name ?? '-' }}</span>
                                                @if($tx->type === 'transfer' && $tx->destinationAccount)
                                                    <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
                                                    <span>{{ $tx->destinationAccount->name }}</span>
                                                @endif
                                                @if($tx->notes)
                                                    <span class="text-slate-300 dark:text-slate-600">•</span>
                                                    <span class="italic truncate max-w-xs">{{ $tx->notes }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kategori -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    @if($tx->type === 'transfer')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 text-[11px] font-semibold">
                                            <span class="material-symbols-outlined text-[13px]">swap_horiz</span>
                                            <span>Transfer Internal</span>
                                        </span>
                                    @elseif($tx->category)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold">
                                            <span class="material-symbols-outlined text-[13px]">{{ $tx->category->icon ?? 'label' }}</span>
                                            <span>{{ $tx->category->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- Nominal -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <span class="font-display font-black text-sm {{ match($tx->type) {
                                        'income' => 'text-emerald-600 dark:text-emerald-400',
                                        'expense' => 'text-rose-600 dark:text-rose-400',
                                        'transfer' => 'text-indigo-600 dark:text-indigo-400',
                                        default => 'text-slate-900 dark:text-white'
                                    } }}">
                                        {{ $tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '') }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Struk -->
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    @if($tx->attachment)
                                        <button @click="viewReceipt('{{ asset('storage/' . $tx->attachment) }}', '{{ addslashes($tx->description) }}')" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 text-[11px] font-semibold transition-colors" title="Lihat Struk">
                                            <span class="material-symbols-outlined text-[14px]">image</span>
                                            <span>Lihat</span>
                                        </button>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">-</span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEdit({{ $tx->toJson() }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Edit Transaksi">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <form action="{{ route('transactions.destroy', $tx) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
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
                                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">receipt_long</span>
                                    <p class="font-medium text-sm text-slate-600 dark:text-slate-400">Tidak ada transaksi yang cocok dengan kriteria filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Receipt Lightbox Modal -->
        <div x-show="showReceiptModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showReceiptModal" x-transition.opacity @click="showReceiptModal = false" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showReceiptModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white truncate" x-text="previewTitle"></h3>
                        <button @click="showReceiptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                    <div class="flex items-center justify-center max-h-[70vh] overflow-auto rounded-2xl bg-slate-50 dark:bg-slate-950 p-2">
                        <img :src="previewUrl" class="max-w-full max-h-[65vh] object-contain rounded-xl shadow-md">
                    </div>
                    <div class="mt-4 flex justify-end">
                        <a :href="previewUrl" target="_blank" download class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">download</span>
                            <span>Unduh File</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import CSV Modal with 2-Step Interactive Review Wizard -->
        <div x-show="showImportModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showImportModal" x-transition.opacity @click="showImportModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showImportModal" x-transition.scale.origin-center 
                     :class="importStep === 2 ? 'sm:max-w-4xl' : 'sm:max-w-xl'"
                     class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-sm">
                                <span class="material-symbols-outlined text-[24px]">upload_file</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="importStep === 1 ? 'Import Transaksi CSV' : 'Review &amp; Konfirmasi Data CSV'"></h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase"
                                          :class="importStep === 1 ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300'"
                                          x-text="importStep === 1 ? 'Langkah 1: Unggah' : 'Langkah 2: Review'"></span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="importStep === 1 ? 'Unggah file spreadsheet CSV mutasi bank Anda untuk ditinjau' : 'Tinjau baris transaksi sebelum disimpan ke pembukuan'"></p>
                            </div>
                        </div>
                        <button @click="showImportModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- STEP 1: UPLOAD & SELECT FILE -->
                    <div x-show="importStep === 1" class="space-y-5">
                        <!-- Template Download Alert Card -->
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-50/80 to-blue-50/60 dark:from-indigo-950/30 dark:to-slate-900 border border-indigo-100 dark:border-indigo-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                            <div class="flex items-start sm:items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                                    <span class="material-symbols-outlined text-[20px]">description</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-indigo-950 dark:text-indigo-200">Belum punya format file CSV?</p>
                                    <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-0.5">Unduh template CSV resmi yang siap Anda isi di Excel atau Google Sheets.</p>
                                </div>
                            </div>
                            <a href="{{ route('transactions.export.template') }}" class="flex-shrink-0 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-800/80 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm">
                                <span class="material-symbols-outlined text-[16px]">download</span>
                                <span>Unduh Template CSV</span>
                            </a>
                        </div>

                        <!-- Dropzone & File Input -->
                        <div @dragover.prevent @drop.prevent="handleFileDrop($event)" class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl p-7 text-center transition-all bg-slate-50/60 dark:bg-slate-800/30">
                            <input type="file" id="csv-file-input" @change="handleCsvFileSelect($event)" accept=".csv,text/csv" class="hidden">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-3xl">cloud_upload</span>
                            </div>
                            <h4 class="font-display font-bold text-sm text-slate-800 dark:text-white mb-1">
                                Pilih File CSV atau Tarik (Drag &amp; Drop) ke Sini
                            </h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 max-w-sm mx-auto">
                                Otomatis mendeteksi pemisah koma (<code class="font-mono">,</code>), titik koma (<code class="font-mono">;</code>), tab, serta format tanggal Excel (<code class="font-mono">DD/MM/YYYY</code> atau <code class="font-mono">YYYY-MM-DD</code>).
                            </p>
                            <button type="button" @click="document.getElementById('csv-file-input').click()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-md shadow-indigo-600/20 active:scale-95 inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">attach_file</span>
                                <span>Pilih File CSV</span>
                            </button>
                        </div>

                        <!-- Fallback Wallet Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Dompet Cadangan (Default Wallet)
                            </label>
                            <select x-model="defaultImportAccountId" class="w-full text-xs font-medium rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5 px-3">
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Transaksi akan dimasukkan ke dompet ini jika kolom dompet pada file CSV tidak diisi atau tidak cocok dengan daftar akun Anda.
                            </p>
                        </div>

                        <!-- Parsing Indicator -->
                        <div x-show="isParsingCsv" class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-xs flex items-center justify-center gap-2 animate-pulse">
                            <span class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                            <span>Membaca dan memvalidasi baris data transaksi...</span>
                        </div>

                        <!-- Error Alert -->
                        <div x-show="csvError" x-cloak class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[18px] text-rose-600 dark:text-rose-400 flex-shrink-0 mt-0.5">error</span>
                            <span x-text="csvError"></span>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                            <button type="button" @click="showImportModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Tutup
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: REVIEW & PREVIEW DATA TABLE -->
                    <div x-show="importStep === 2" class="space-y-5">
                        <!-- File info bar -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-xl">description</span>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-white">
                                        File: <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="csvFileName"></span>
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Periksa kembali data di bawah ini sebelum disimpan ke database. Anda dapat menghapus baris yang tidak diinginkan.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">Dompet Cadangan:</label>
                                <select x-model="defaultImportAccountId" class="text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-1 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Statistics Bento Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Transaksi</span>
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">receipt_long</span>
                                </div>
                                <p class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                                    <span x-text="previewRows.length"></span> <span class="text-xs font-normal text-slate-500">baris</span>
                                </p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Total Pemasukan</span>
                                    <span class="material-symbols-outlined text-emerald-500 text-[18px]">arrow_downward</span>
                                </div>
                                <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                                    +Rp <span x-text="totalIncomePreviewFormatted"></span>
                                </p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-800/40">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-rose-700 dark:text-rose-300">Total Pengeluaran</span>
                                    <span class="material-symbols-outlined text-rose-500 text-[18px]">arrow_upward</span>
                                </div>
                                <p class="text-xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">
                                    -Rp <span x-text="totalExpensePreviewFormatted"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Interactive Review Table -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                            <div class="max-h-[320px] overflow-y-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800/95 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700 z-10">
                                        <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            <th class="py-2.5 px-3 w-10 text-center">#</th>
                                            <th class="py-2.5 px-3">Tanggal</th>
                                            <th class="py-2.5 px-3">Tipe</th>
                                            <th class="py-2.5 px-3">Dompet</th>
                                            <th class="py-2.5 px-3">Kategori</th>
                                            <th class="py-2.5 px-3 text-right">Nominal</th>
                                            <th class="py-2.5 px-3">Deskripsi &amp; Catatan</th>
                                            <th class="py-2.5 px-3 text-center w-12">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                        <template x-for="(row, idx) in previewRows" :key="idx">
                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                                <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]" x-text="idx + 1"></td>
                                                <td class="py-2.5 px-3 font-medium text-slate-800 dark:text-slate-200 whitespace-nowrap" x-text="row.transaction_date"></td>
                                                <td class="py-2.5 px-3 whitespace-nowrap">
                                                    <template x-if="row.type === 'income'">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                            <span class="material-symbols-outlined text-[12px]">arrow_downward</span> Pemasukan
                                                        </span>
                                                    </template>
                                                    <template x-if="row.type === 'expense'">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                            <span class="material-symbols-outlined text-[12px]">arrow_upward</span> Pengeluaran
                                                        </span>
                                                    </template>
                                                    <template x-if="row.type === 'transfer'">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                                            <span class="material-symbols-outlined text-[12px]">sync_alt</span> Transfer
                                                        </span>
                                                    </template>
                                                </td>
                                                <td class="py-2.5 px-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                        <span class="material-symbols-outlined text-[12px] text-slate-400">account_balance_wallet</span>
                                                        <span x-text="row.account_name || 'Dompet Cadangan'"></span>
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-medium bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300">
                                                        <span class="material-symbols-outlined text-[12px]">sell</span>
                                                        <span x-text="row.category_name || (row.type === 'transfer' ? 'Transfer' : 'Umum')"></span>
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-bold whitespace-nowrap" :class="row.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                                    <span x-text="row.type === 'income' ? '+' : '-'"></span>Rp <span x-text="formatRupiah(row.amount)"></span>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <p class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[220px]" x-text="row.description"></p>
                                                    <p class="text-[10px] text-slate-400 truncate max-w-[220px]" x-text="row.notes" x-show="row.notes"></p>
                                                </td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <button type="button" @click="removePreviewRow(idx)" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Hapus baris ini dari import">
                                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Form Submission -->
                        <form action="{{ route('transactions.import.csv') }}" method="POST">
                            @csrf
                            <input type="hidden" name="transactions_json" :value="JSON.stringify(previewRows)">
                            <input type="hidden" name="default_account_id" :value="defaultImportAccountId">

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <button type="button" @click="importStep = 1" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                                    <span>Pilih File Lain</span>
                                </button>

                                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                                    <button type="button" @click="showImportModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                        Batal
                                    </button>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>Konfirmasi &amp; Simpan (<span x-text="previewRows.length"></span> Transaksi)</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function transactionsManager() {
        return {
            showForm: false,
            isEdit: false,
            formAction: '{{ route('transactions.store') }}',
            type: 'expense',
            amount: '',
            transaction_date: '{{ date('Y-m-d') }}',
            account_id: '{{ $accounts->first()->id ?? '' }}',
            destination_account_id: '',
            category_id: '{{ $categories->first()->id ?? '' }}',
            description: '',
            notes: '',
            existingAttachment: null,
            
            // Receipt Lightbox modal
            showReceiptModal: false,
            previewUrl: '',
            previewTitle: '',

            // Import Modal & Review State
            showImportModal: false,
            importStep: 1,
            csvFileName: '',
            csvError: '',
            isParsingCsv: false,
            previewRows: [],
            defaultImportAccountId: '{{ $accounts->first()->id ?? '' }}',
            totalIncomePreview: 0,
            totalExpensePreview: 0,
            totalIncomePreviewFormatted: '0',
            totalExpensePreviewFormatted: '0',

            openCreate() {
                this.isEdit = false;
                this.formAction = '{{ route('transactions.store') }}';
                this.type = 'expense';
                this.amount = '';
                this.transaction_date = '{{ date('Y-m-d') }}';
                this.account_id = '{{ $accounts->first()->id ?? '' }}';
                this.destination_account_id = '';
                this.category_id = '{{ $categories->first()->id ?? '' }}';
                this.description = '';
                this.notes = '';
                this.existingAttachment = null;
                this.showForm = true;
                this.$nextTick(() => {
                    document.getElementById('transaction-form-card')?.scrollIntoView({ behavior: 'smooth' });
                });
            },

            openEdit(tx) {
                this.isEdit = true;
                this.formAction = '/transactions/' + tx.id;
                this.type = tx.type;
                this.amount = cleanNominal(tx.amount);
                this.transaction_date = tx.transaction_date ? tx.transaction_date.substring(0, 10) : '{{ date('Y-m-d') }}';
                this.account_id = tx.account_id;
                this.destination_account_id = tx.destination_account_id || '';
                this.category_id = tx.category_id || '';
                this.description = tx.description;
                this.notes = tx.notes || '';
                this.existingAttachment = tx.attachment ? '/storage/' + tx.attachment : null;
                this.showForm = true;
                this.$nextTick(() => {
                    document.getElementById('transaction-form-card')?.scrollIntoView({ behavior: 'smooth' });
                });
            },

            viewReceipt(url, title) {
                this.previewUrl = url;
                this.previewTitle = title;
                this.showReceiptModal = true;
            },

            openImportModal() {
                this.importStep = 1;
                this.csvFileName = '';
                this.csvError = '';
                this.isParsingCsv = false;
                this.previewRows = [];
                this.defaultImportAccountId = '{{ $accounts->first()->id ?? '' }}';
                this.showImportModal = true;
                const fileInput = document.getElementById('csv-file-input');
                if (fileInput) fileInput.value = '';
            },

            handleCsvFileSelect(event) {
                const file = event.target.files ? event.target.files[0] : null;
                if (!file) return;
                this.csvFileName = file.name;
                this.csvError = '';
                this.isParsingCsv = true;

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const text = e.target.result;
                        const rows = this.parseCsvText(text);
                        if (!rows || rows.length === 0) {
                            this.csvError = 'Tidak ditemukan data transaksi yang valid dalam file CSV ini. Pastikan format kolom sesuai template dan baris memiliki nominal.';
                            this.isParsingCsv = false;
                            return;
                        }
                        this.previewRows = rows;
                        this.calculatePreviewTotals();
                        this.importStep = 2;
                        this.isParsingCsv = false;
                    } catch (err) {
                        console.error('CSV parse error:', err);
                        this.csvError = 'Terjadi kesalahan saat memproses file CSV: ' + err.message;
                        this.isParsingCsv = false;
                    }
                };
                reader.onerror = () => {
                    this.csvError = 'Gagal membaca file dari penyimpanan lokal.';
                    this.isParsingCsv = false;
                };
                reader.readAsText(file);
            },

            handleFileDrop(e) {
                if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        this.csvError = 'Hanya file dengan ekstensi .csv yang didukung.';
                        return;
                    }
                    const input = document.getElementById('csv-file-input');
                    if (input) {
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        input.files = dataTransfer.files;
                    }
                    this.handleCsvFileSelect({ target: { files: [file] } });
                }
            },

            parseCsvText(text) {
                if (text.charCodeAt(0) === 0xFEFF) {
                    text = text.substring(1);
                }
                const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim().length > 0);
                if (lines.length < 2) return [];

                // Detect delimiter
                const sample = lines.slice(0, 3).join('\n');
                const semicolons = (sample.match(/;/g) || []).length;
                const tabs = (sample.match(/\t/g) || []).length;
                const commas = (sample.match(/,/g) || []).length;

                let delimiter = ',';
                if (semicolons > commas && semicolons >= tabs) {
                    delimiter = ';';
                } else if (tabs > commas && tabs > semicolons) {
                    delimiter = '\t';
                }

                const parseLine = (line) => {
                    const result = [];
                    let cur = '';
                    let inQuotes = false;
                    for (let i = 0; i < line.length; i++) {
                        const char = line[i];
                        const next = line[i + 1];
                        if (char === '"') {
                            if (inQuotes && next === '"') {
                                cur += '"';
                                i++;
                            } else {
                                inQuotes = !inQuotes;
                            }
                        } else if (char === delimiter && !inQuotes) {
                            result.push(cur.trim());
                            cur = '';
                        } else {
                            cur += char;
                        }
                    }
                    result.push(cur.trim());
                    return result;
                };

                const rawHeaders = parseLine(lines[0]);
                const headers = rawHeaders.map(h => h.toLowerCase().trim().replace(/^["']|["']$/g, ''));

                let dateIdx = 0, typeIdx = 1, accIdx = 2, catIdx = 3, amtIdx = 4, descIdx = 5, notesIdx = 6;
                headers.forEach((h, idx) => {
                    if (h.includes('date') || h.includes('tanggal') || h.includes('tgl')) dateIdx = idx;
                    else if (h.includes('type') || h.includes('tipe') || h.includes('jenis')) typeIdx = idx;
                    else if (h.includes('account') || h.includes('dompet') || h.includes('rekening') || h.includes('akun')) accIdx = idx;
                    else if (h.includes('cat') || h.includes('kategori')) catIdx = idx;
                    else if (h.includes('amount') || h.includes('nominal') || h.includes('jumlah') || h.includes('total')) amtIdx = idx;
                    else if (h.includes('desc') || h.includes('keterangan') || h.includes('deskripsi')) descIdx = idx;
                    else if (h.includes('note') || h.includes('catatan')) notesIdx = idx;
                });

                const parsed = [];
                for (let i = 1; i < lines.length; i++) {
                    const cols = parseLine(lines[i]);
                    if (cols.length < 2 || cols.every(c => c === '')) continue;

                    let rawDate = (cols[dateIdx] || '').trim();
                    if (!rawDate) {
                        rawDate = '{{ date('Y-m-d') }}';
                    }

                    let rawType = (cols[typeIdx] || 'expense').toLowerCase().trim();
                    let normType = 'expense';
                    if (rawType.includes('in') || rawType.includes('masuk')) normType = 'income';
                    else if (rawType.includes('trans') || rawType.includes('pindah')) normType = 'transfer';

                    let rawAmount = cols[amtIdx] || '0';
                    let cleanAmt = cleanNominal(rawAmount);
                    let numAmount = parseFloat(cleanAmt) || 0;
                    if (numAmount <= 0) continue;

                    parsed.push({
                        id: i,
                        transaction_date: rawDate,
                        type: normType,
                        account_name: (cols[accIdx] || '').trim(),
                        category_name: (cols[catIdx] || '').trim(),
                        amount: cleanAmt,
                        description: (cols[descIdx] || '').trim() || 'Import Transaksi',
                        notes: (cols[notesIdx] || '').trim()
                    });
                }

                return parsed;
            },

            removePreviewRow(index) {
                this.previewRows.splice(index, 1);
                this.calculatePreviewTotals();
                if (this.previewRows.length === 0) {
                    this.importStep = 1;
                    this.csvError = 'Semua baris dalam daftar pratinjau telah dihapus.';
                }
            },

            calculatePreviewTotals() {
                let income = 0;
                let expense = 0;
                this.previewRows.forEach(r => {
                    let val = parseFloat(r.amount) || 0;
                    if (r.type === 'income') income += val;
                    else if (r.type === 'expense') expense += val;
                });
                this.totalIncomePreview = income;
                this.totalExpensePreview = expense;
                this.totalIncomePreviewFormatted = formatRupiah(income);
                this.totalExpensePreviewFormatted = formatRupiah(expense);
            }
        };
    }
    </script>
</x-app-layout>

