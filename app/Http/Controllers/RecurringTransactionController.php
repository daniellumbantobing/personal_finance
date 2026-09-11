<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecurringTransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $recurring = $user->recurringTransactions()
            ->with(['account', 'category'])
            ->orderBy('next_run_date')
            ->get();

        $accounts = $user->accounts()->orderBy('name')->get();
        $categories = Category::where('user_id', $user->id)
            ->orWhere('is_default', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $recurringTransactions = $recurring;
        $activeMonthly = $recurring->where('type', 'expense')->where('is_active', true)->sum(function ($r) {
            return match($r->frequency) {
                'daily' => $r->amount * 30,
                'weekly' => $r->amount * 4.33,
                'monthly' => $r->amount,
                'yearly' => $r->amount / 12,
                default => $r->amount,
            };
        });

        return view('recurring.index', compact('recurringTransactions', 'accounts', 'categories', 'activeMonthly'));
    }

    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'required|date',
            'description' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);

        RecurringTransaction::create($validated);

        return redirect()->route('recurring.index')->with('success', 'Jadwal transaksi rutin baru berhasil disimpan.');
    }

    public function update(Request $request, RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'required|date',
            'description' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $recurring->update($validated);

        return redirect()->route('recurring.index')->with('success', 'Transaksi rutin berhasil diperbarui.');
    }

    public function destroy(RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== Auth::id()) {
            abort(403);
        }

        $recurring->delete();

        return redirect()->route('recurring.index')->with('success', 'Transaksi rutin berhasil dihapus.');
    }

    public function processNow(RecurringTransaction $recurring, FinanceService $financeService)
    {
        if ($recurring->user_id !== Auth::id()) {
            abort(403);
        }

        // 1. Eksekusi transaksi real di dompet
        $financeService->createTransaction([
            'user_id' => $recurring->user_id,
            'account_id' => $recurring->account_id,
            'category_id' => $recurring->category_id,
            'type' => $recurring->type,
            'amount' => $recurring->amount,
            'transaction_date' => now()->toDateString(),
            'description' => '[Rutin] ' . $recurring->description,
            'notes' => 'Eksekusi transaksi berulang frekuensi ' . $recurring->frequency,
        ]);

        // 2. Majukan jadwal jatuh tempo berikutnya
        $currentDate = $recurring->next_run_date ? Carbon::parse($recurring->next_run_date) : now();
        $nextDate = match ($recurring->frequency) {
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };

        $recurring->update(['next_run_date' => $nextDate->toDateString()]);

        return redirect()->route('recurring.index')->with('success', "Transaksi '{$recurring->description}' berhasil dieksekusi & jadwal dimajukan ke {$nextDate->translatedFormat('d M Y')}.");
    }
}

