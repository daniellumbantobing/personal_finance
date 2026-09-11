<x-app-layout>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="space-y-8 pb-12" x-data="reportsManager()" x-init="initCharts()">
        <!-- Header & Month-Year Filter -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">analytics</span>
                    <span>Analisis Finansial Cerdas</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Laporan &amp; Analisis
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Evaluasi arus kas, tren 6 bulan, dan distribusi pengeluaran per kategori.
                </p>
            </div>

            <!-- Month & Year Filter Form -->
            <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
                <select name="month" onchange="this.form.submit()" class="rounded-2xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500/20 shadow-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>

                <select name="year" onchange="this.form.submit()" class="rounded-2xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-indigo-500/20 shadow-sm">
                    @for($y = \Carbon\Carbon::now()->year; $y >= \Carbon\Carbon::now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <noscript>
                    <button type="submit" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold">Filter</button>
                </noscript>
            </form>
        </div>

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pemasukan</span>
                <p class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">Rp {{ number_format($monthIncome, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-400">Periode terpilih</span>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengeluaran</span>
                <p class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2">Rp {{ number_format($monthExpense, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-400">Periode terpilih</span>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Arus Kas Bersih (Net)</span>
                <p class="font-display text-2xl font-bold {{ $monthNet >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400' }} mt-2">
                    {{ $monthNet >= 0 ? '+' : '' }} Rp {{ number_format($monthNet, 0, ',', '.') }}
                </p>
                <span class="text-[11px] text-slate-400">{{ $monthNet >= 0 ? 'Surplus bulanan' : 'Defisit (Pengeluaran > Pemasukan)' }}</span>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rasio Tabungan (Savings Rate)</span>
                <p class="font-display text-2xl font-bold text-slate-900 dark:text-white mt-2">{{ $savingsRate }}%</p>
                <span class="text-[11px] {{ $savingsRate >= 20 ? 'text-emerald-600 font-semibold' : 'text-slate-400' }}">
                    {{ $savingsRate >= 20 ? 'Sangat Sehat (≥20%)' : 'Rekomendasi minimal 20%' }}
                </span>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- 6-Month Cashflow Line/Bar Chart (8 cols) -->
            <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Tren Arus Kas (6 Bulan)</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Perbandingan pemasukan vs pengeluaran setiap bulan</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1.5 text-emerald-600">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Pemasukan
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-indigo-600">
                            <span class="w-3 h-3 rounded-full bg-indigo-500"></span> Pengeluaran
                        </span>
                    </div>
                </div>

                <div class="relative h-72 w-full">
                    <canvas id="cashflowTrendChart"></canvas>
                </div>
            </div>

            <!-- Expense Category Breakdown Donut Chart (4 cols) -->
            <div class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Alokasi Belanja</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Proporsi pos pengeluaran bulan ini</p>
                </div>

                <div class="relative h-56 my-4 flex items-center justify-center">
                    @if(count($categoryData) > 0)
                        <canvas id="categoryDonutChart"></canvas>
                    @else
                        <div class="text-center text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-[32px] block mb-1">pie_chart</span>
                            Belum ada data belanja di bulan ini
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 text-center">
                    Total Kategori Terpakai: <span class="font-bold text-slate-800 dark:text-slate-200">{{ count($categoryLabels) }}</span>
                </div>
            </div>
        </div>

        <!-- Top Expenses Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-card">
            <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-4">Pengeluaran Terbesar Bulan Ini</h3>

            @if(count($topTransactions) > 0)
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($topTransactions as $tx)
                        <div class="py-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-slate-900 dark:text-white">{{ $tx->description ?? $tx->category->name }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $tx->category->name ?? 'Tanpa Kategori' }} • {{ $tx->account->name }} • {{ $tx->transaction_date->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                            <span class="font-display font-bold text-sm text-slate-900 dark:text-white">
                                - Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400 text-center py-6">Tidak ada catatan transaksi pengeluaran pada bulan ini.</p>
            @endif
        </div>
    </div>

    <script>
    function reportsManager() {
        return {
            trendChart: null,
            donutChart: null,

            initCharts() {
                this.$nextTick(() => {
                    this.renderTrendChart(@json($trendLabels), @json($trendIncome), @json($trendExpense));
                    this.renderDonutChart(@json($categoryLabels), @json($categoryData));
                });
            },

            renderTrendChart(labels, income, expense) {
                const ctx = document.getElementById('cashflowTrendChart');
                if (!ctx) return;

                if (this.trendChart) {
                    this.trendChart.destroy();
                }

                this.trendChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: income,
                                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                borderRadius: 8,
                            },
                            {
                                label: 'Pengeluaran',
                                data: expense,
                                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                                borderRadius: 8,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(226, 232, 240, 0.4)' },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) return (value / 1000000) + 'jt';
                                        if (value >= 1000) return (value / 1000) + 'rb';
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            renderDonutChart(labels, data) {
                const ctx = document.getElementById('categoryDonutChart');
                if (!ctx) return;

                if (this.donutChart) {
                    this.donutChart.destroy();
                }

                if (!data || data.length === 0) return;

                const palette = [
                    '#6366f1', '#ec4899', '#10b981', '#f59e0b', '#3b82f6',
                    '#8b5cf6', '#14b8a6', '#f43f5e', '#64748b'
                ];

                this.donutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: palette.slice(0, data.length),
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    }
    </script>
</x-app-layout>

