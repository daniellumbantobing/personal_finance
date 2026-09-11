<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi — {{ config('app.name', 'Personal Finance') }} ({{ auth()->user()->name }})</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            padding: 24px;
            font-size: 12px;
            line-height: 1.5;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05);
        }
        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #0f172a; color: #ffffff; }
        .btn-primary:hover { background: #334155; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0f172a;
        }
        .brand {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .brand span { color: #4f46e5; }
        .doc-title {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-top: 4px;
        }
        .meta-info {
            text-align: right;
            font-size: 11px;
            color: #64748b;
        }
        .meta-info strong { color: #0f172a; }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .card-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .card-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
        }
        .text-emerald { color: #059669; }
        .text-rose { color: #e11d48; }
        .text-indigo { color: #4f46e5; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-income { background: #dcfce7; color: #166534; }
        .badge-expense { background: #ffe4e6; color: #9f1239; }
        .badge-transfer { background: #e0e7ff; color: #3730a3; }
        
        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .container { padding: 0; box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="no-print">
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">← Kembali ke Aplikasi</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak / Simpan PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="brand">Personal<span>Finance</span> <span style="font-size:12px; font-weight:600; color:#64748b;">FinAI Pro</span></div>
            <div class="doc-title">Laporan Rekapitulasi Arus Kas &amp; Transaksi</div>
        </div>
        <div class="meta-info">
            <div>Pengguna: <strong>{{ auth()->user()->name }}</strong></div>
            <div>Email: {{ auth()->user()->email }}</div>
            <div>Dicetak: <strong>{{ now()->translatedFormat('d F Y, H:i') }} WIB</strong></div>
        </div>
    </div>

    <div class="summary-cards">
        <div class="card">
            <div class="card-label">Total Pemasukan</div>
            <div class="card-value text-emerald">+Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="card-label">Total Pengeluaran</div>
            <div class="card-value text-rose">-Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="card-label">Arus Kas Bersih (Net)</div>
            <div class="card-value {{ $netTotal >= 0 ? 'text-emerald' : 'text-rose' }}">
                {{ $netTotal >= 0 ? '+' : '' }}Rp {{ number_format($netTotal, 0, ',', '.') }}
            </div>
        </div>
        <div class="card">
            <div class="card-label">Banyak Transaksi</div>
            <div class="card-value text-indigo">{{ $transactions->count() }} Data</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 85px;">Tanggal</th>
                <th style="width: 75px;">Tipe</th>
                <th>Deskripsi &amp; Catatan</th>
                <th>Kategori</th>
                <th>Dompet / Akun</th>
                <th style="text-align: right;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
                @php
                    $isIncome = $t->type === 'income';
                    $isTransfer = $t->type === 'transfer';
                    $badgeClass = $isIncome ? 'badge-income' : ($isTransfer ? 'badge-transfer' : 'badge-expense');
                    $badgeText = $isIncome ? 'Pemasukan' : ($isTransfer ? 'Transfer' : 'Pengeluaran');
                    $amountColor = $isIncome ? 'text-emerald' : ($isTransfer ? 'text-indigo' : 'text-rose');
                    $prefix = $isIncome ? '+' : ($isTransfer ? '' : '-');
                @endphp
                <tr>
                    <td>{{ $t->transaction_date->format('d/m/Y') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                    <td>
                        <strong style="color: #0f172a;">{{ $t->description }}</strong>
                        @if($t->notes)
                            <div style="font-size: 10px; color: #64748b;">{{ $t->notes }}</div>
                        @endif
                    </td>
                    <td>{{ $t->category->name ?? ($isTransfer ? 'Transfer Antar Akun' : '-') }}</td>
                    <td>
                        {{ $t->account->name ?? '-' }}
                        @if($isTransfer && $t->destinationAccount)
                            → {{ $t->destinationAccount->name }}
                        @endif
                    </td>
                    <td style="text-align: right;" class="{{ $amountColor }}">
                        <strong>{{ $prefix }}Rp {{ number_format($t->amount, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">
                        Tidak ada catatan transaksi pada filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Dokumen ini digenerate secara otomatis oleh FinAI Intelligence Telemetry.</div>
        <div>Halaman 1 / 1</div>
    </div>
</div>

</body>
</html>

