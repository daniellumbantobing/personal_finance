<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currency = $user->currency ?? 'IDR';
        $currentMonthName = Carbon::now()->translatedFormat('F');

        // 1. Accounts & Total Balance
        $accounts = $user->accounts()->orderBy('name')->get();
        $accountsCount = $accounts->count();
        $totalBalance = (float) $accounts->sum('balance');

        // 2. Current Month Income & Expense
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netSavings = $monthlyIncome - $monthlyExpense;
        $expenseRatio = $monthlyIncome > 0
            ? min(100, round(($monthlyExpense / $monthlyIncome) * 100, 1))
            : ($monthlyExpense > 0 ? 100 : 0);

        // 3. Financial Score & Savings Rate
        $savingsRate = $monthlyIncome > 0
            ? max(0, round(($netSavings / $monthlyIncome) * 100))
            : 0;

        if ($monthlyIncome > 0) {
            if ($savingsRate >= 30) {
                $financialScore = 92;
                $scoreGrade = 'Level A • Sangat Sehat';
            } elseif ($savingsRate >= 20) {
                $financialScore = 82;
                $scoreGrade = 'Level A • Sehat';
            } elseif ($savingsRate >= 10) {
                $financialScore = 68;
                $scoreGrade = 'Level B • Cukup';
            } else {
                $financialScore = 55;
                $scoreGrade = 'Level C • Waspada';
            }
        } elseif ($monthlyExpense > 0) {
            $financialScore = 40;
            $scoreGrade = 'Level D • Defisit';
        } else {
            $financialScore = 50;
            $scoreGrade = 'Netral • Baru Mulai';
        }

        // 4. MoM Growth (Month-over-Month)
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $lastMonthIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $lastMonthExpense = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $lastMonthNet = $lastMonthIncome - $lastMonthExpense;

        if ($lastMonthNet > 0) {
            $momGrowth = round((($netSavings - $lastMonthNet) / $lastMonthNet) * 100, 1);
        } else {
            $momGrowth = $netSavings > 0 ? 100 : 0;
        }

        // 5. Transaction Counts & Activity
        $recentTransactions = $user->transactions()
            ->with(['account', 'category', 'destinationAccount'])
            ->latest('transaction_date')
            ->latest('id')
            ->take(6)
            ->get();

        $totalTransactionsCount = $user->transactions()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $incomeTransactionsCount = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        $expenseTransactionsCount = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        // 6. Top Spending Categories
        $topCategories = $user->transactions()
            ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->take(4)
            ->with('category')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'name' => $row->category->name ?? 'Lainnya',
                    'icon' => $row->category->icon ?? 'label',
                    'total' => (float) $row->total,
                ];
            });

        $topCategoriesTotal = $topCategories->sum('total');
        $topCategoriesPct = $monthlyExpense > 0 ? round(($topCategoriesTotal / $monthlyExpense) * 100) : 0;

        // 7. Active Budget, Goal, Recurring, and Due Debt
        $activeBudget = $user->budgets()->with('category')->first();
        $activeGoal = $user->savingGoals()->where('status', 'active')->first();
        $dueRecurring = $user->recurringTransactions()
            ->where('is_active', true)
            ->where('next_run_date', '<=', Carbon::now()->addDays(7))
            ->first();

        $dueDebt = $user->debts()
            ->where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('due_date')
            ->first();

        // 8. 6-Month Cashflow Real Trend Data
        $sixMonthsTrend = [];
        $maxVal = 1;
        for ($i = 5; $i >= 0; $i--) {
            $mStart = Carbon::now()->subMonths($i)->startOfMonth();
            $mEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $in = (float) $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');
            $out = (float) $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            if ($in > $maxVal) $maxVal = $in;
            if ($out > $maxVal) $maxVal = $out;

            $sixMonthsTrend[] = [
                'month' => Carbon::now()->subMonths($i)->translatedFormat('M'),
                'income' => $in,
                'expense' => $out,
                'is_current' => $i === 0,
            ];
        }

        foreach ($sixMonthsTrend as &$item) {
            $item['in_pct'] = max(6, round(($item['income'] / $maxVal) * 100));
            $item['out_pct'] = max(6, round(($item['expense'] / $maxVal) * 100));
        }
        unset($item);

        $totalSurplus = array_sum(array_map(fn($t) => $t['income'] - $t['expense'], $sixMonthsTrend));
        $avgMonthlySurplus = round($totalSurplus / 6);

        return view('dashboard', compact(
            'currency',
            'currentMonthName',
            'accounts',
            'accountsCount',
            'totalBalance',
            'monthlyIncome',
            'monthlyExpense',
            'netSavings',
            'expenseRatio',
            'savingsRate',
            'financialScore',
            'scoreGrade',
            'momGrowth',
            'recentTransactions',
            'totalTransactionsCount',
            'incomeTransactionsCount',
            'expenseTransactionsCount',
            'topCategories',
            'topCategoriesTotal',
            'topCategoriesPct',
            'activeBudget',
            'activeGoal',
            'dueRecurring',
            'dueDebt',
            'sixMonthsTrend',
            'avgMonthlySurplus'
        ));
    }
}

