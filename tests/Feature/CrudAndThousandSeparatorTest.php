<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\RecurringTransaction;
use App\Models\SavingGoal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudAndThousandSeparatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::first() ?? User::factory()->create();
        $this->account = Account::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Dompet Test'],
            ['type' => 'bank', 'balance' => 10000000, 'currency' => 'IDR']
        );
        $this->category = Category::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Makan & Minum Test'],
            ['type' => 'expense', 'color' => '#f43f5e', 'icon' => 'restaurant']
        );
    }

    public function test_budget_crud_with_thousand_separator(): void
    {
        // 1. Create with string amount containing thousand separator and omitted end_date
        $response = $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $this->category->id,
            'amount' => '2.500.000',
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('budgets.index'));

        $budget = Budget::where('user_id', $this->user->id)
            ->where('category_id', $this->category->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($budget);
        $this->assertEquals(2500000, (int)$budget->amount);
        $this->assertNotNull($budget->end_date);

        // 2. Update with thousand separator
        $updateResponse = $this->actingAs($this->user)->put(route('budgets.update', $budget), [
            'category_id' => $this->category->id,
            'amount' => '3.750.000',
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $budget->refresh();
        $this->assertEquals(3750000, (int)$budget->amount);

        // 3. Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('budgets.destroy', $budget));
        $deleteResponse->assertRedirect(route('budgets.index'));
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_account_crud_with_thousand_separator(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)->post(route('accounts.store'), [
            'name' => 'Rekening Test Baru',
            'type' => 'bank',
            'balance' => '5.000.000',
            'currency' => 'IDR',
        ]);

        $response->assertSessionHasNoErrors();
        $account = Account::where('user_id', $this->user->id)
            ->where('name', 'Rekening Test Baru')
            ->first();

        $this->assertNotNull($account);
        $this->assertEquals(5000000, (int)$account->balance);

        // 2. Update
        $updateResponse = $this->actingAs($this->user)->put(route('accounts.update', $account), [
            'name' => 'Rekening Test Diperbarui',
            'type' => 'bank',
            'balance' => '6.500.000',
            'currency' => 'IDR',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $account->refresh();
        $this->assertEquals(6500000, (int)$account->balance);

        // 3. Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('accounts.destroy', $account));
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_transaction_crud_with_thousand_separator(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '125.000',
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan Siang Test',
        ]);

        $response->assertSessionHasNoErrors();
        $tx = Transaction::where('user_id', $this->user->id)
            ->where('description', 'Makan Siang Test')
            ->latest('id')
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(125000, (int)$tx->amount);

        // 2. Update
        $updateResponse = $this->actingAs($this->user)->put(route('transactions.update', $tx), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '150.000',
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan Siang & Kopi Test',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $tx->refresh();
        $this->assertEquals(150000, (int)$tx->amount);

        // 3. Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('transactions.destroy', $tx));
        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);
    }

    public function test_saving_goal_crud_and_deposit_with_thousand_separator(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)->post(route('saving-goals.store'), [
            'name' => 'Beli Laptop Test',
            'target_amount' => '15.000.000',
            'current_amount' => '1.000.000',
            'target_date' => now()->addYear()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $goal = SavingGoal::where('user_id', $this->user->id)
            ->where('name', 'Beli Laptop Test')
            ->first();

        $this->assertNotNull($goal);
        $this->assertEquals(15000000, (int)$goal->target_amount);
        $this->assertEquals(1000000, (int)$goal->current_amount);

        // 2. Deposit
        $depositResponse = $this->actingAs($this->user)->post(route('saving-goals.deposit', $goal), [
            'account_id' => $this->account->id,
            'amount' => '500.000',
            'notes' => 'Tabungan awal bulan',
        ]);

        $depositResponse->assertSessionHasNoErrors();
        $goal->refresh();
        $this->assertEquals(1500000, (int)$goal->current_amount);

        // 3. Update
        $updateResponse = $this->actingAs($this->user)->put(route('saving-goals.update', $goal), [
            'name' => 'Beli Laptop Pro Test',
            'target_amount' => '18.000.000',
            'current_amount' => '1.500.000',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $goal->refresh();
        $this->assertEquals(18000000, (int)$goal->target_amount);

        // 4. Delete
        $this->actingAs($this->user)->delete(route('saving-goals.destroy', $goal));
        $this->assertDatabaseMissing('saving_goals', ['id' => $goal->id]);
    }

    public function test_recurring_transaction_crud_with_thousand_separator(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)->post(route('recurring.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '350.000',
            'frequency' => 'monthly',
            'next_run_date' => now()->addMonth()->toDateString(),
            'description' => 'Langganan Internet Test',
        ]);

        $response->assertSessionHasNoErrors();
        $recurring = RecurringTransaction::where('user_id', $this->user->id)
            ->where('description', 'Langganan Internet Test')
            ->first();

        $this->assertNotNull($recurring);
        $this->assertEquals(350000, (int)$recurring->amount);

        // 2. Update
        $updateResponse = $this->actingAs($this->user)->put(route('recurring.update', $recurring), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '400.000',
            'frequency' => 'monthly',
            'next_run_date' => now()->addMonth()->toDateString(),
            'description' => 'Langganan Internet Upgrade Test',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $recurring->refresh();
        $this->assertEquals(400000, (int)$recurring->amount);

        // 3. Delete
        $this->actingAs($this->user)->delete(route('recurring.destroy', $recurring));
        $this->assertDatabaseMissing('recurring_transactions', ['id' => $recurring->id]);
    }

    public function test_debt_crud_and_payment_with_thousand_separator(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'type' => 'payable',
            'person_name' => 'Sahabat Test',
            'total_amount' => '2.000.000',
            'due_date' => now()->addMonths(2)->toDateString(),
            'account_id' => $this->account->id,
            'affect_wallet' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $debt = Debt::where('user_id', $this->user->id)
            ->where('person_name', 'Sahabat Test')
            ->latest('id')
            ->first();

        $this->assertNotNull($debt);
        $this->assertEquals(2000000, (int)$debt->total_amount);

        // 2. Pay installment
        $payResponse = $this->actingAs($this->user)->post(route('debts.pay', $debt), [
            'payment_account_id' => $this->account->id,
            'payment_amount' => '500.000',
            'payment_date' => now()->toDateString(),
            'payment_notes' => 'Cicilan 1',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $debt->refresh();
        $this->assertEquals(500000, (int)$debt->paid_amount);
        $this->assertEquals(1500000, (int)$debt->remaining_amount);

        // 3. Update
        $updateResponse = $this->actingAs($this->user)->put(route('debts.update', $debt), [
            'type' => 'payable',
            'person_name' => 'Sahabat Karib Test',
            'total_amount' => '2.500.000',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $debt->refresh();
        $this->assertEquals(2500000, (int)$debt->total_amount);

        // 4. Delete
        $this->actingAs($this->user)->delete(route('debts.destroy', $debt));
        $this->assertDatabaseMissing('debts', ['id' => $debt->id]);
    }

    public function test_decimal_string_inputs_do_not_add_two_zeros_across_all_menus(): void
    {
        // 1. Saving Goal with .00 decimal string
        $goal = SavingGoal::create([
            'user_id' => $this->user->id,
            'name' => 'Dana Darurat Test',
            'target_amount' => 10000000,
            'current_amount' => 2000000,
            'status' => 'active',
        ]);

        $this->actingAs($this->user)->put(route('saving-goals.update', $goal), [
            'name' => 'Dana Darurat Test',
            'target_amount' => '10000000.00',
            'current_amount' => '2000000.00',
        ])->assertSessionHasNoErrors();

        $goal->refresh();
        $this->assertEquals(10000000, (int)$goal->target_amount, 'SavingGoal target_amount must not gain 2 zeros');
        $this->assertEquals(2000000, (int)$goal->current_amount, 'SavingGoal current_amount must not gain 2 zeros');

        // 2. Budget with .00 decimal string
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 1500000,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);

        $this->actingAs($this->user)->put(route('budgets.update', $budget), [
            'category_id' => $this->category->id,
            'amount' => '1500000.00',
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
        ])->assertSessionHasNoErrors();

        $budget->refresh();
        $this->assertEquals(1500000, (int)$budget->amount, 'Budget amount must not gain 2 zeros');

        // 3. Account with .00 decimal string
        $account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Rekening Tabungan',
            'type' => 'bank',
            'balance' => 25000000,
            'currency' => 'IDR',
        ]);

        $this->actingAs($this->user)->put(route('accounts.update', $account), [
            'name' => 'Rekening Tabungan',
            'type' => 'bank',
            'balance' => '25000000.00',
            'currency' => 'IDR',
        ])->assertSessionHasNoErrors();

        $account->refresh();
        $this->assertEquals(25000000, (int)$account->balance, 'Account balance must not gain 2 zeros');

        // 4. Transaction with .00 decimal string
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 75000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan Siang',
        ]);

        $this->actingAs($this->user)->put(route('transactions.update', $tx), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '75000.00',
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan Siang',
        ])->assertSessionHasNoErrors();

        $tx->refresh();
        $this->assertEquals(75000, (int)$tx->amount, 'Transaction amount must not gain 2 zeros');

        // 5. Recurring with .00 decimal string
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'monthly',
            'next_run_date' => now()->addMonth()->toDateString(),
            'description' => 'Tagihan Listrik',
        ]);

        $this->actingAs($this->user)->put(route('recurring.update', $recurring), [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => '150000.00',
            'frequency' => 'monthly',
            'next_run_date' => now()->addMonth()->toDateString(),
            'description' => 'Tagihan Listrik',
        ])->assertSessionHasNoErrors();

        $recurring->refresh();
        $this->assertEquals(150000, (int)$recurring->amount, 'Recurring amount must not gain 2 zeros');

        // 6. Debt with .00 decimal string
        $debt = Debt::create([
            'user_id' => $this->user->id,
            'type' => 'payable',
            'person_name' => 'Teman',
            'total_amount' => 5000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->user)->put(route('debts.update', $debt), [
            'type' => 'payable',
            'person_name' => 'Teman',
            'total_amount' => '5000000.00',
        ])->assertSessionHasNoErrors();

        $debt->refresh();
        $this->assertEquals(5000000, (int)$debt->total_amount, 'Debt total_amount must not gain 2 zeros');
    }
}
