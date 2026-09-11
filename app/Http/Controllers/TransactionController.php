<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $user->transactions()->with(['account', 'destinationAccount', 'category']);

        // 1. Search text
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        // 2. Filter Type
        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'transfer'])) {
            $query->where('type', $request->type);
        }

        // 3. Filter Account
        if ($request->filled('account_id') && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }

        // 4. Filter Category
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 5. Filter Period
        $period = $request->get('period', 'this_month');
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        if ($period === 'this_month') {
            $dateFrom = Carbon::now()->startOfMonth()->toDateString();
            $dateTo = Carbon::now()->endOfMonth()->toDateString();
            $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        } elseif ($period === 'last_month') {
            $dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
            $dateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
            $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        } elseif ($period === 'last_30_days') {
            $dateFrom = Carbon::now()->subDays(30)->toDateString();
            $dateTo = Carbon::now()->toDateString();
            $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        } elseif ($period === 'custom' && !empty($dateFrom) && !empty($dateTo)) {
            $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        }

        // 6. Filter Attachment
        if ($request->boolean('with_attachment')) {
            $query->whereNotNull('attachment')->where('attachment', '!=', '');
        }

        $transactions = $query->latest('transaction_date')->latest('id')->get();
        $accounts = $user->accounts()->orderBy('name')->get();
        $categories = Category::where('user_id', $user->id)
            ->orWhere('is_default', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $summaryIncome = (float) $transactions->where('type', 'income')->sum('amount');
        $summaryExpense = (float) $transactions->where('type', 'expense')->sum('amount');
        $summaryNet = $summaryIncome - $summaryExpense;

        return view('transactions.index', compact('transactions', 'accounts', 'categories', 'period', 'dateFrom', 'dateTo', 'summaryIncome', 'summaryExpense', 'summaryNet'));
    }

    public function store(Request $request, FinanceService $financeService)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:2048',
        ];

        if ($request->type === 'transfer') {
            $rules['destination_account_id'] = 'required|exists:accounts,id|different:account_id';
        } else {
            $rules['category_id'] = 'required|exists:categories,id';
        }

        $validated = $request->validate($rules);
        $validated['user_id'] = Auth::id();
        $validated['category_id'] = $request->type === 'transfer' ? null : $request->category_id;
        $validated['destination_account_id'] = $request->type === 'transfer' ? $request->destination_account_id : null;

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $financeService->createTransaction($validated);

        return redirect()->route('transactions.index')->with('success', 'Transaksi baru berhasil dicatat.');
    }

    public function update(Request $request, Transaction $transaction, FinanceService $financeService)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:2048',
        ];

        if ($request->type === 'transfer') {
            $rules['destination_account_id'] = 'required|exists:accounts,id|different:account_id';
        } else {
            $rules['category_id'] = 'required|exists:categories,id';
        }

        $validated = $request->validate($rules);
        $validated['category_id'] = $request->type === 'transfer' ? null : $request->category_id;
        $validated['destination_account_id'] = $request->type === 'transfer' ? $request->destination_account_id : null;

        if ($request->hasFile('attachment')) {
            if ($transaction->attachment) {
                Storage::disk('public')->delete($transaction->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        } elseif ($request->boolean('remove_attachment')) {
            if ($transaction->attachment) {
                Storage::disk('public')->delete($transaction->attachment);
            }
            $validated['attachment'] = null;
        }

        $financeService->updateTransaction($transaction, $validated);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction, FinanceService $financeService)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        if ($transaction->attachment) {
            Storage::disk('public')->delete($transaction->attachment);
        }

        $financeService->deleteTransaction($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $query = $user->transactions()->with(['account', 'destinationAccount', 'category']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'transfer'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('account_id') && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest('transaction_date')->latest('id')->get();
        $filename = 'transaksi_finai_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Tanggal', 'Tipe', 'Dompet Asal', 'Dompet Tujuan', 'Kategori', 'Nominal', 'Deskripsi', 'Catatan', 'Lampiran']);

            foreach ($transactions as $t) {
                $typeLabel = match($t->type) {
                    'income' => 'Pemasukan',
                    'expense' => 'Pengeluaran',
                    'transfer' => 'Transfer',
                    default => $t->type
                };
                fputcsv($handle, [
                    $t->id,
                    $t->transaction_date->format('Y-m-d'),
                    $typeLabel,
                    $t->account->name ?? '-',
                    $t->destinationAccount->name ?? '-',
                    $t->category->name ?? ($t->type === 'transfer' ? 'Transfer' : '-'),
                    $t->amount,
                    $t->description,
                    $t->notes ?? '',
                    $t->attachment ? asset('storage/' . $t->attachment) : '-'
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPrint(Request $request)
    {
        $user = Auth::user();
        $query = $user->transactions()->with(['account', 'destinationAccount', 'category']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'transfer'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('account_id') && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest('transaction_date')->latest('id')->get();
        $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');
        $totalExpense = (float) $transactions->where('type', 'expense')->sum('amount');
        $netTotal = $totalIncome - $totalExpense;

        return view('transactions.print', compact('transactions', 'totalIncome', 'totalExpense', 'netTotal'));
    }

    /**
     * Download sample CSV template for transactions import.
     */
    public function exportTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_transaksi.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header kolom
            fputcsv($handle, ['Tanggal', 'Tipe', 'Dompet', 'Kategori', 'Nominal', 'Deskripsi', 'Catatan']);

            // Contoh baris data
            fputcsv($handle, [date('Y-m-d'), 'income', 'GOPAY', 'Gaji & Bonus', '5000000', 'Gaji Bulanan', 'Transfer dari kantor']);
            fputcsv($handle, [date('Y-m-d'), 'expense', 'SEA BANK', 'Makan & Minum', '75000', 'Makan Siang Resto', 'Makan bersama tim']);
            fputcsv($handle, [date('Y-m-d'), 'expense', 'GOPAY', 'Tagihan & Utilitas', '150000', 'Token Listrik PLN', 'Nomor meter 12345678']);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Process transactions import from CSV file or reviewed transactions array.
     */
    public function importCsv(Request $request, FinanceService $financeService)
    {
        $user = Auth::user();

        // 1. Build lookup tables for accounts and categories (case-insensitive & trimmed)
        $accounts = [];
        foreach ($user->accounts as $acc) {
            $accounts[strtolower(trim($acc->name))] = $acc->id;
        }

        $categories = [];
        $userCategories = Category::where('user_id', $user->id)->orWhere('is_default', true)->get();
        foreach ($userCategories as $cat) {
            $categories[strtolower(trim($cat->name))] = $cat->id;
        }

        $defaultAccount = $user->accounts()->first();
        $defaultCategory = $userCategories->first();
        $fallbackAccountId = $request->input('default_account_id', $defaultAccount?->id);

        $rowsToImport = [];

        // CASE A: Pre-reviewed JSON transactions from interactive review modal
        if ($request->filled('transactions_json')) {
            $decoded = json_decode($request->input('transactions_json'), true);
            if (is_array($decoded)) {
                $rowsToImport = $decoded;
            }
        } elseif ($request->has('transactions') && is_array($request->transactions)) {
            $rowsToImport = $request->transactions;
        }
        // CASE B: Direct CSV file upload
        elseif ($request->hasFile('csv_file')) {
            $request->validate([
                'csv_file' => 'required|file|max:5120',
            ]);

            $path = $request->file('csv_file')->getRealPath();
            $file = fopen($path, 'r');
            if (!$file) {
                return back()->with('error', 'Gagal membuka file CSV yang diunggah.');
            }

            // Check BOM
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($file);
            }

            // Auto-detect delimiter
            $firstLine = fgets($file);
            $delimiter = ',';
            if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                $delimiter = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $delimiter = "\t";
            }
            rewind($file);
            if ($bom === "\xEF\xBB\xBF") {
                fread($file, 3);
            }

            // Read header to identify column positions
            $header = fgetcsv($file, 0, $delimiter);
            $lowerHeaders = is_array($header) ? array_map('strtolower', array_map('trim', $header)) : [];

            $dateIdx = 0; $typeIdx = 1; $accIdx = 2; $catIdx = 3; $amtIdx = 4; $descIdx = 5; $notesIdx = 6;
            foreach ($lowerHeaders as $idx => $h) {
                if (str_contains($h, 'date') || str_contains($h, 'tanggal') || str_contains($h, 'tgl')) $dateIdx = $idx;
                if (str_contains($h, 'type') || str_contains($h, 'tipe') || str_contains($h, 'jenis')) $typeIdx = $idx;
                if (str_contains($h, 'account') || str_contains($h, 'dompet') || str_contains($h, 'rekening') || str_contains($h, 'akun')) $accIdx = $idx;
                if (str_contains($h, 'categor') || str_contains($h, 'kategori')) $catIdx = $idx;
                if (str_contains($h, 'amount') || str_contains($h, 'nominal') || str_contains($h, 'jumlah') || str_contains($h, 'total')) $amtIdx = $idx;
                if (str_contains($h, 'desc') || str_contains($h, 'keterangan') || str_contains($h, 'deskripsi')) $descIdx = $idx;
                if (str_contains($h, 'note') || str_contains($h, 'catatan')) $notesIdx = $idx;
            }

            while (($cols = fgetcsv($file, 0, $delimiter)) !== false) {
                if (count($cols) < 2 || empty(trim(implode('', $cols)))) continue;

                $rowsToImport[] = [
                    'transaction_date' => $cols[$dateIdx] ?? date('Y-m-d'),
                    'type' => $cols[$typeIdx] ?? 'expense',
                    'account_name' => $cols[$accIdx] ?? '',
                    'category_name' => $cols[$catIdx] ?? '',
                    'amount' => $cols[$amtIdx] ?? 0,
                    'description' => $cols[$descIdx] ?? 'Import Transaksi',
                    'notes' => $cols[$notesIdx] ?? '',
                ];
            }
            fclose($file);
        } else {
            return back()->with('error', 'Tidak ada file CSV atau data transaksi yang dipilih.');
        }

        if (empty($rowsToImport)) {
            return back()->with('error', 'Tidak ada baris transaksi valid yang ditemukan untuk diimpor.');
        }

        $importedCount = 0;
        DB::transaction(function () use ($rowsToImport, $user, $accounts, $categories, $defaultAccount, $defaultCategory, $fallbackAccountId, $financeService, &$importedCount) {
            foreach ($rowsToImport as $row) {
                $rawAmount = $row['amount'] ?? 0;
                $amount = FinanceService::sanitizeNominal($rawAmount);
                if ($amount <= 0) continue;

                // 1. Flexible Date Parsing
                $rawDate = trim((string)($row['transaction_date'] ?? ''));
                try {
                    $cleanDate = str_replace('/', '-', $rawDate);
                    $date = Carbon::parse($cleanDate)->toDateString();
                } catch (\Exception $e) {
                    $date = Carbon::now()->toDateString();
                }

                // 2. Type matching
                $rawType = strtolower(trim((string)($row['type'] ?? 'expense')));
                if (str_contains($rawType, 'in') || str_contains($rawType, 'masuk')) {
                    $type = 'income';
                } elseif (str_contains($rawType, 'trans') || str_contains($rawType, 'pindah')) {
                    $type = 'transfer';
                } else {
                    $type = 'expense';
                }

                // 3. Account matching
                $accountId = null;
                if (!empty($row['account_id']) && in_array($row['account_id'], array_values($accounts))) {
                    $accountId = (int)$row['account_id'];
                } else {
                    $accKey = strtolower(trim((string)($row['account_name'] ?? '')));
                    $accountId = $accounts[$accKey] ?? null;
                    if (!$accountId && !empty($accKey)) {
                        foreach ($accounts as $name => $id) {
                            if (str_contains($name, $accKey) || str_contains($accKey, $name)) {
                                $accountId = $id;
                                break;
                            }
                        }
                    }
                }
                $accountId = $accountId ?? $fallbackAccountId ?? $defaultAccount?->id;
                if (!$accountId) continue;

                // 4. Category matching
                $categoryId = null;
                if (!empty($row['category_id']) && in_array($row['category_id'], array_values($categories))) {
                    $categoryId = (int)$row['category_id'];
                } else {
                    $catKey = strtolower(trim((string)($row['category_name'] ?? '')));
                    $categoryId = $categories[$catKey] ?? null;
                    if (!$categoryId && !empty($catKey)) {
                        foreach ($categories as $name => $id) {
                            if (str_contains($name, $catKey) || str_contains($catKey, $name)) {
                                $categoryId = $id;
                                break;
                            }
                        }
                    }
                }
                $categoryId = $type === 'transfer' ? null : ($categoryId ?? $defaultCategory?->id);

                // 5. Description & Notes
                $description = !empty(trim((string)($row['description'] ?? ''))) ? trim((string)$row['description']) : 'Import Transaksi';
                $notes = !empty(trim((string)($row['notes'] ?? ''))) ? trim((string)$row['notes']) : null;

                $financeService->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $accountId,
                    'category_id' => $categoryId,
                    'type' => $type,
                    'amount' => $amount,
                    'transaction_date' => $date,
                    'description' => $description,
                    'notes' => $notes,
                ]);

                $importedCount++;
            }
        });

        if ($importedCount === 0) {
            return back()->with('error', 'Gagal mengimpor data. Pastikan nominal lebih dari 0 dan format baris valid.');
        }

        return redirect()->route('transactions.index')->with('success', "Berhasil mengimpor {$importedCount} transaksi ke dalam pembukuan Anda.");
    }
}

