<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\SavingGoalController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // 1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 3. Standard MVC Logout
    Route::post('logout', function (\Illuminate\Http\Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');

    // 4. Accounts (Dompet & Rekening)
    Route::resource('accounts', AccountController::class)->except(['create', 'show', 'edit']);

    // 5. Categories
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);

    // 6. Transactions
    Route::get('transactions/export/template', [TransactionController::class, 'exportTemplate'])->name('transactions.export.template');
    Route::get('transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export.csv');
    Route::get('transactions/export/print', [TransactionController::class, 'exportPrint'])->name('transactions.export.print');
    Route::post('transactions/import/csv', [TransactionController::class, 'importCsv'])->name('transactions.import.csv');
    Route::resource('transactions', TransactionController::class)->except(['create', 'show', 'edit']);

    // 7. Budgets
    Route::resource('budgets', BudgetController::class)->except(['create', 'show', 'edit']);

    // 8. Saving Goals
    Route::post('saving-goals/{savingGoal}/deposit', [SavingGoalController::class, 'deposit'])->name('saving-goals.deposit');
    Route::resource('saving-goals', SavingGoalController::class)->except(['create', 'show', 'edit']);

    // 9. Recurring Transactions
    Route::post('recurring/{recurring}/process', [RecurringTransactionController::class, 'processNow'])->name('recurring.process');
    Route::resource('recurring', RecurringTransactionController::class)->except(['create', 'show', 'edit']);

    // 10. Financial Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // 11. Debts & Loans
    Route::post('debts/{debt}/pay', [DebtController::class, 'pay'])->name('debts.pay');
    Route::delete('debt-payments/{payment}', [DebtController::class, 'deletePayment'])->name('debts.payments.destroy');
    Route::resource('debts', DebtController::class)->except(['create', 'show', 'edit']);
});

require __DIR__.'/auth.php';
