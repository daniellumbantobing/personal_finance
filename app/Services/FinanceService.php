<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Exception;

class FinanceService
{
    /**
     * Create a new transaction and update the account balance.
     */
    public function createTransaction(array $data)
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create($data);

            $this->updateAccountBalance($transaction);

            return $transaction;
        });
    }

    /**
     * Update an existing transaction and adjust the account balance.
     */
    public function updateTransaction(Transaction $transaction, array $data)
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Revert old balance
            $this->revertAccountBalance($transaction);

            // Update transaction
            $transaction->update($data);

            // Apply new balance
            $this->updateAccountBalance($transaction);

            return $transaction;
        });
    }

    /**
     * Delete a transaction and revert the account balance.
     */
    public function deleteTransaction(Transaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            $this->revertAccountBalance($transaction);
            $transaction->delete();
        });
    }

    /**
     * Helper to apply transaction amount to account balance.
     */
    protected function updateAccountBalance(Transaction $transaction)
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->balance += $transaction->amount;
            $account->save();
        } elseif ($transaction->type === 'expense') {
            $account->balance -= $transaction->amount;
            $account->save();
        } elseif ($transaction->type === 'transfer') {
            $account->balance -= $transaction->amount;
            $account->save();

            if ($transaction->destination_account_id) {
                $destination = $transaction->destinationAccount;
                $destination->balance += $transaction->amount;
                $destination->save();
            }
        }
    }

    /**
     * Helper to revert transaction amount from account balance.
     */
    protected function revertAccountBalance(Transaction $transaction)
    {
        $account = $transaction->account;

        if ($transaction->type === 'income') {
            $account->balance -= $transaction->amount;
            $account->save();
        } elseif ($transaction->type === 'expense') {
            $account->balance += $transaction->amount;
            $account->save();
        } elseif ($transaction->type === 'transfer') {
            $account->balance += $transaction->amount;
            $account->save();

            if ($transaction->destination_account_id) {
                $destination = $transaction->destinationAccount;
                $destination->balance -= $transaction->amount;
                $destination->save();
            }
        }
    }

    /**
     * Create a debt and optionally affect the linked wallet balance.
     */
    public function createDebt(array $debtData, bool $affectWallet = false)
    {
        return DB::transaction(function () use ($debtData, $affectWallet) {
            $debt = \App\Models\Debt::create($debtData);

            if ($affectWallet && !empty($debt->account_id)) {
                $account = $debt->account;
                if ($debt->type === 'payable') {
                    // Hutang (Pinjaman masuk ke kas Anda)
                    $account->balance += $debt->total_amount;
                    $account->save();

                    Transaction::create([
                        'user_id' => $debt->user_id,
                        'account_id' => $debt->account_id,
                        'type' => 'income',
                        'amount' => $debt->total_amount,
                        'transaction_date' => now()->toDateString(),
                        'description' => 'Pencairan Pinjaman: ' . $debt->person_name,
                        'notes' => $debt->description,
                    ]);
                } elseif ($debt->type === 'receivable') {
                    // Piutang (Kas Anda keluar dipinjam orang lain)
                    $account->balance -= $debt->total_amount;
                    $account->save();

                    Transaction::create([
                        'user_id' => $debt->user_id,
                        'account_id' => $debt->account_id,
                        'type' => 'expense',
                        'amount' => $debt->total_amount,
                        'transaction_date' => now()->toDateString(),
                        'description' => 'Pinjaman Diberikan: ' . $debt->person_name,
                        'notes' => $debt->description,
                    ]);
                }
            }

            return $debt;
        });
    }

    /**
     * Record a debt payment (installment or full payoff).
     */
    public function recordDebtPayment(\App\Models\Debt $debt, array $paymentData)
    {
        return DB::transaction(function () use ($debt, $paymentData) {
            $account = Account::findOrFail($paymentData['account_id']);
            $amount = (float) $paymentData['amount'];

            // 1. Create transaction in cashflow
            $txType = $debt->type === 'payable' ? 'expense' : 'income';
            $txDesc = $debt->type === 'payable'
                ? 'Bayar Cicilan Hutang: ' . $debt->person_name
                : 'Terima Pelunasan Piutang: ' . $debt->person_name;

            $transaction = Transaction::create([
                'user_id' => $debt->user_id,
                'account_id' => $account->id,
                'type' => $txType,
                'amount' => $amount,
                'transaction_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                'description' => $txDesc,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // 2. Adjust account balance
            if ($debt->type === 'payable') {
                $account->balance -= $amount;
            } else {
                $account->balance += $amount;
            }
            $account->save();

            // 3. Create debt payment record
            $paymentData['transaction_id'] = $transaction->id;
            $payment = $debt->payments()->create($paymentData);

            // 4. Update debt paid amount and status
            $debt->paid_amount += $amount;
            if ($debt->paid_amount >= $debt->total_amount) {
                $debt->status = 'paid';
            } else {
                $debt->status = 'partial';
            }
            $debt->save();

            return $payment;
        });
    }

    /**
     * Delete a debt payment and revert balances.
     */
    public function deleteDebtPayment(\App\Models\DebtPayment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $debt = $payment->debt;
            $account = $payment->account;
            $amount = (float) $payment->amount;

            // Revert account balance
            if ($debt->type === 'payable') {
                $account->balance += $amount;
            } else {
                $account->balance -= $amount;
            }
            $account->save();

            // Revert debt paid amount
            $debt->paid_amount = max(0, $debt->paid_amount - $amount);
            if ($debt->paid_amount == 0) {
                $debt->status = 'unpaid';
            } elseif ($debt->paid_amount < $debt->total_amount) {
                $debt->status = 'partial';
            }
            $debt->save();

            // Delete associated transaction if any
            if ($payment->transaction_id) {
                Transaction::where('id', $payment->transaction_id)->delete();
            }

            $payment->delete();
        });
    }

    /**
     * Clean and sanitize numeric currency input from user forms or DB strings.
     * Handles:
     * - "10.000.000" (thousand separator) -> 10000000
     * - "10000000.00" (DB decimal) -> 10000000
     * - "10000000" -> 10000000
     * - "10.000.000,00" (Indonesian formatted with decimals) -> 10000000
     * - "-5.000.000" (negative balance) -> -5000000
     */
    public static function sanitizeNominal($value, bool $allowNegative = false): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $str = trim((string)$value);
        $isNegative = $allowNegative && str_starts_with($str, '-');

        // 1. If it has .00 or .50 decimal cents at the end (e.g. "10000.00" or "10,000.00")
        if (preg_match('/\.\d{1,2}$/', $str)) {
            $str = preg_replace('/\.\d{1,2}$/', '', $str);
        }
        // 2. If it has comma as decimal cents, e.g. ",00" or ",50" at the end (e.g. "10.000,00")
        elseif (preg_match('/,\d{1,2}$/', $str)) {
            $str = preg_replace('/,\d{1,2}$/', '', $str);
        }

        // 3. Remove everything except digits
        $digits = preg_replace('/[^0-9]/', '', $str);
        if ($digits === '') {
            return 0.0;
        }

        $num = (float)$digits;
        return $isNegative ? -$num : $num;
    }
}
