<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtController extends Controller
{
    /**
     * Display a listing of debts & loans.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $debts = $user->debts()->with(['account', 'payments.account'])->latest('id')->get();
        $accounts = $user->accounts()->orderBy('name')->get();

        return view('debts.index', compact('debts', 'accounts'));
    }

    /**
     * Store a newly created debt record in storage.
     */
    public function store(Request $request, FinanceService $financeService)
    {
        if ($request->has('total_amount')) {
            $request->merge(['total_amount' => FinanceService::sanitizeNominal($request->total_amount)]);
        }

        $validated = $request->validate([
            'type' => 'required|in:payable,receivable',
            'person_name' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
            'affect_wallet' => 'nullable|boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;

        $financeService->createDebt($validated, $request->boolean('affect_wallet'));

        return back()->with('success', 'Catatan pinjaman baru berhasil disimpan.');
    }

    /**
     * Update the specified debt record in storage.
     */
    public function update(Request $request, Debt $debt)
    {
        if ($debt->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('total_amount')) {
            $request->merge(['total_amount' => FinanceService::sanitizeNominal($request->total_amount)]);
        }

        $validated = $request->validate([
            'type' => 'required|in:payable,receivable',
            'person_name' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);

        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;

        $debt->update($validated);

        return back()->with('success', 'Data pinjaman berhasil diperbarui.');
    }

    /**
     * Remove the specified debt record from storage.
     */
    public function destroy(Debt $debt)
    {
        if ($debt->user_id !== Auth::id()) {
            abort(403);
        }

        $debt->delete();

        return back()->with('success', 'Pinjaman berhasil dihapus.');
    }

    /**
     * Record an installment payment for the debt.
     */
    public function pay(Request $request, Debt $debt, FinanceService $financeService)
    {
        if ($debt->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('payment_amount')) {
            $request->merge(['payment_amount' => FinanceService::sanitizeNominal($request->payment_amount)]);
        }

        $maxAmount = $debt->remaining_amount;
        $validated = $request->validate([
            'payment_account_id' => 'required|exists:accounts,id',
            'payment_amount' => 'required|numeric|min:1|max:' . $maxAmount,
            'payment_date' => 'required|date',
            'payment_notes' => 'nullable|string',
        ]);

        $financeService->recordDebtPayment($debt, [
            'account_id' => $validated['payment_account_id'],
            'amount' => (float) $validated['payment_amount'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['payment_notes'] ?? null,
        ]);

        return back()->with('success', 'Pembayaran cicilan berhasil dicatat & saldo dompet telah disesuaikan.');
    }

    /**
     * Delete/revert a debt payment history.
     */
    public function deletePayment(DebtPayment $payment, FinanceService $financeService)
    {
        if ($payment->debt->user_id !== Auth::id()) {
            abort(403);
        }

        $financeService->deleteDebtPayment($payment);

        return back()->with('success', 'Histori pembayaran berhasil dibatalkan & saldo dipulihkan.');
    }
}

