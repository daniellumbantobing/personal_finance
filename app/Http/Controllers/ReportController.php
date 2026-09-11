<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();

        // 1. Ringkasan Bulan Terpilih
        $monthlyIncome = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $monthlyExpense = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $netCashflow = $monthlyIncome - $monthlyExpense;
        $savingsRate = $monthlyIncome > 0
            ? max(0, round(($netCashflow / $monthlyIncome) * 100, 1))
            : 0;

        // 2. Alokasi Pengeluaran per Kategori (Donut Chart)
        $categoryBreakdown = $user->transactions()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get();

        $donutLabels = [];
        $donutData = [];
        $donutColors = ['#6366f1', '#f43f5e', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];

        foreach ($categoryBreakdown as $idx => $item) {
            $donutLabels[] = $item->category->name ?? 'Lainnya';
            $donutData[] = (float) $item->total;
        }

        // 3. Tren 6 Bulan Terakhir (Bar / Line Chart)
        $sixMonthLabels = [];
        $sixMonthIncome = [];
        $sixMonthExpense = [];

        for ($i = 5; $i >= 0; $i--) {
            $mStart = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonths($i)->startOfMonth();
            $mEnd = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonths($i)->endOfMonth();

            $in = (float) $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            $out = (float) $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            $sixMonthLabels[] = $mStart->translatedFormat('M Y');
            $sixMonthIncome[] = $in;
            $sixMonthExpense[] = $out;
        }

        // 4. 5 Pengeluaran Terbesar Bulan Ini
        $topExpenses = $user->transactions()
            ->with(['account', 'category'])
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderByDesc('amount')
            ->take(5)
            ->get();

        // Aliases for view compatibility
        $monthIncome = $monthlyIncome;
        $monthExpense = $monthlyExpense;
        $monthNet = $netCashflow;
        $trendLabels = $sixMonthLabels;
        $trendIncome = $sixMonthIncome;
        $trendExpense = $sixMonthExpense;
        $categoryLabels = $donutLabels;
        $categoryData = $donutData;
        $topTransactions = $topExpenses;

        return view('reports.index', compact(
            'selectedMonth',
            'selectedYear',
            'monthIncome',
            'monthExpense',
            'monthNet',
            'savingsRate',
            'trendLabels',
            'trendIncome',
            'trendExpense',
            'categoryLabels',
            'categoryData',
            'topTransactions'
        ));
    }
}

