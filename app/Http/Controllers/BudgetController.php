<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $budgets = $user->budgets()->with('category')->latest('id')->get();
        $categories = Category::where('type', 'expense')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('is_default', true);
            })
            ->orderBy('name')
            ->get();

        $totalBudgeted = (float) $budgets->sum('amount');
        $totalSpent = (float) $budgets->sum('spent');
        $overallPercentage = $totalBudgeted > 0 ? min(100, round(($totalSpent / $totalBudgeted) * 100)) : 0;

        return view('budgets.index', compact('budgets', 'categories', 'totalBudgeted', 'totalSpent', 'overallPercentage'));
    }

    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'period' => 'required|in:monthly,weekly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if (empty($validated['end_date'])) {
            $start = Carbon::parse($validated['start_date']);
            $validated['end_date'] = match($validated['period']) {
                'weekly' => $start->copy()->addWeek()->subDay()->toDateString(),
                'yearly' => $start->copy()->addYear()->subDay()->toDateString(),
                default => $start->copy()->endOfMonth()->toDateString(),
            };
        }

        $validated['user_id'] = Auth::id();
        Budget::create($validated);

        return redirect()->route('budgets.index')->with('success', 'Batas anggaran kategori berhasil ditetapkan.');
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->has('amount')) {
            $request->merge(['amount' => FinanceService::sanitizeNominal($request->amount)]);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:1',
            'period' => 'required|in:monthly,weekly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if (empty($validated['end_date'])) {
            $start = Carbon::parse($validated['start_date']);
            $validated['end_date'] = match($validated['period']) {
                'weekly' => $start->copy()->addWeek()->subDay()->toDateString(),
                'yearly' => $start->copy()->addYear()->subDay()->toDateString(),
                default => $start->copy()->endOfMonth()->toDateString(),
            };
        }

        $budget->update($validated);

        return redirect()->route('budgets.index')->with('success', 'Anggaran berhasil diperbarui.');
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'Anggaran berhasil dihapus.');
    }
}

