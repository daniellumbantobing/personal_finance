<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all due recurring transactions and advance next run dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();

        $dueItems = RecurringTransaction::where('is_active', true)
            ->where('next_run_date', '<=', $today)
            ->with(['account'])
            ->get();

        $this->info("Found {$dueItems->count()} recurring transactions due for processing.");

        $processed = 0;

        foreach ($dueItems as $item) {
            DB::transaction(function () use ($item) {
                // 1. Create Transaction
                Transaction::create([
                    'user_id' => $item->user_id,
                    'account_id' => $item->account_id,
                    'category_id' => $item->category_id,
                    'type' => $item->type,
                    'amount' => $item->amount,
                    'transaction_date' => Carbon::now()->toDateString(),
                    'description' => '[Otomatis] ' . $item->description,
                ]);

                // 2. Adjust Balance
                if ($item->account) {
                    if ($item->type === 'expense') {
                        $item->account->decrement('balance', $item->amount);
                    } elseif ($item->type === 'income') {
                        $item->account->increment('balance', $item->amount);
                    }
                }

                // 3. Advance Next Run Date
                $nextDate = Carbon::parse($item->next_run_date);
                $nextDate = match($item->frequency) {
                    'daily' => $nextDate->addDay(),
                    'weekly' => $nextDate->addWeek(),
                    'monthly' => $nextDate->addMonth(),
                    'yearly' => $nextDate->addYear(),
                    default => $nextDate->addMonth(),
                };

                $item->update(['next_run_date' => $nextDate->toDateString()]);
            });

            $processed++;
        }

        $this->info("Successfully processed {$processed} recurring transactions.");
    }
}

