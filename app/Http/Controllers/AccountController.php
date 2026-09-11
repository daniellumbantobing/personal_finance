<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Auth::user()->accounts()->orderBy('name')->get();
        $totalBalance = (float) $accounts->sum('balance');

        return view('accounts.index', compact('accounts', 'totalBalance'));
    }

    public function store(Request $request)
    {
        if ($request->has('balance')) {
            $request->merge(['balance' => FinanceService::sanitizeNominal($request->balance, true)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,ewallet,cash,credit_card,investment,other',
            'balance' => 'required|numeric',
            'currency' => 'required|string|max:5',
        ]);

        $validated['user_id'] = Auth::id();
        Account::create($validated);

        return redirect()->route('accounts.index')->with('success', 'Dompet/Rekening baru berhasil ditambahkan.');
    }

    public function update(Request $request, Account $account)
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('balance')) {
            $request->merge(['balance' => FinanceService::sanitizeNominal($request->balance, true)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,ewallet,cash,credit_card,investment,other',
            'balance' => 'required|numeric',
            'currency' => 'required|string|max:5',
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')->with('success', 'Data dompet berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Dompet berhasil dihapus.');
    }
}

