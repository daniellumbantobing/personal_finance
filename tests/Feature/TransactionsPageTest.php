<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_page_renders_successfully(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 5000000,
            'color' => '#3b82f6',
        ]);
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => 'restaurant',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => date('Y-m-d'),
            'description' => 'Makan Siang',
            'notes' => 'Catatan makan siang',
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('Aktivitas &amp; Transaksi', false);
        $response->assertSee('Makan Siang');
    }
}

