<?php

namespace App\Http\Controllers;

use App\Models\SavingGoal;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavingGoalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $goals = $user->savingGoals()->latest('id')->get();
        $accounts = $user->accounts()->orderBy('name')->get();

        $totalTarget = (float) $goals->sum('target_amount');
        $totalSaved = (float) $goals->sum('current_amount');
        $activeGoalsCount = $goals->where('status', 'active')->count();

        return view('saving-goals.index', compact('goals', 'accounts', 'totalTarget', 'totalSaved', 'activeGoalsCount'));
    }

    public function store(Request $request)
    {
        if ($request->has('target_amount')) {
            $request->merge(['target_amount' => FinanceService::sanitizeNominal($request->target_amount)]);
        }
        if ($request->has('current_amount')) {
            $request->merge(['current_amount' => FinanceService::sanitizeNominal($request->current_amount)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['current_amount'] = $validated['current_amount'] ?? 0;
        $validated['status'] = 'active';

        SavingGoal::create($validated);

        return redirect()->route('saving-goals.index')->with('success', 'Target tabungan baru berhasil dibuat.');
    }

    public function update(Request $request, SavingGoal $savingGoal)
    {
        if ($savingGoal->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('target_amount')) {
            $request->merge(['target_amount' => FinanceService::sanitizeNominal($request->target_amount)]);
        }
        if ($request->has('current_amount')) {
            $request->merge(['current_amount' => FinanceService::sanitizeNominal($request->current_amount)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'target_date' => 'nullable|date',
            'status' => 'nullable|in:active,completed,cancelled',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? $savingGoal->status;
        $validated['current_amount'] = $validated['current_amount'] ?? $savingGoal->current_amount;

        $savingGoal->update($validated);

        return redirect()->route('saving-goals.index')->with('success', 'Target tabungan berhasil diperbarui.');
    }

    public function destroy(SavingGoal $savingGoal)
    {
        if ($savingGoal->user_id !== Auth::id()) {
            abort(403);
        }

        $savingGoal->delete();

        return redirect()->route('saving-goals.index')->with('success', 'Target tabungan berhasil dihapus.');
    }

    public function deposit(Request $request, SavingGoal $savingGoal)
    {
        if ($savingGoal->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($savingGoal, $validated) {
            $account = Account::findOrFail($validated['account_id']);
            $amount = (float) $validated['amount'];

            // 1. Potong saldo dompet
            $account->balance -= $amount;
            $account->save();

            // 2. Tambah simpanan di target goal
            $savingGoal->current_amount += $amount;
            if ($savingGoal->current_amount >= $savingGoal->target_amount) {
                $savingGoal->status = 'completed';
            }
            $savingGoal->save();

            // 3. Catat mutasi pengeluaran ke tabungan
            Transaction::create([
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'type' => 'expense',
                'amount' => $amount,
                'transaction_date' => now()->toDateString(),
                'description' => 'Setoran Tabungan: ' . $savingGoal->name,
                'notes' => $validated['notes'] ?? 'Setor tabungan via ' . $account->name,
            ]);
        });

        return redirect()->route('saving-goals.index')->with('success', 'Setoran tabungan berhasil dicatat dan saldo dompet telah disesuaikan.');
    }
}

