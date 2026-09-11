<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Utama',
            'type' => 'bank',
            'balance' => 1000000,
            'color' => '#3b82f6',
        ]);
        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Gaji & Bonus',
            'type' => 'income',
            'color' => '#10b981',
            'icon' => 'payments',
        ]);
    }

    public function test_can_download_csv_template(): void
    {
        $response = $this->actingAs($this->user)->get(route('transactions.export.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="template_import_transaksi.csv"');
        $this->assertStringContainsString('Tanggal', $response->streamedContent());
        $this->assertStringContainsString('Nominal', $response->streamedContent());
    }

    public function test_can_import_csv_via_direct_file_upload_with_semicolon(): void
    {
        $csvContent = "\xEF\xBB\xBF"
            . "Tanggal;Tipe;Dompet;Kategori;Nominal;Deskripsi;Catatan\n"
            . "15/09/2026;income;BCA Utama;Gaji & Bonus;2.500.000;Bonus Kinerja;Proyek Sukses\n"
            . "16/09/2026;expense;BCA Utama;;500.000;Beli Kebutuhan;Bulanan\n";

        $file = UploadedFile::fake()->createWithContent('mutasi.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('transactions.import.csv'), [
            'csv_file' => $file,
            'default_account_id' => $this->account->id,
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 2500000,
            'description' => 'Bonus Kinerja',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 500000,
            'description' => 'Beli Kebutuhan',
        ]);

        // Verify balance updated: 1.000.000 + 2.500.000 - 500.000 = 3.000.000
        $this->account->refresh();
        $this->assertEquals(3000000, $this->account->balance);
    }

    public function test_can_import_reviewed_transactions_json(): void
    {
        $reviewedRows = [
            [
                'transaction_date' => '2026-09-12',
                'type' => 'income',
                'account_name' => 'BCA Utama',
                'category_name' => 'Gaji & Bonus',
                'amount' => '3000000',
                'description' => 'Dividen Investasi',
                'notes' => 'Q3 Return',
            ],
            [
                'transaction_date' => '2026-09-13',
                'type' => 'expense',
                'account_name' => '', // fallback to default account
                'category_name' => '',
                'amount' => '150.000,00', // formatted nominal
                'description' => 'Belanja Supermarket',
                'notes' => '',
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.import.csv'), [
            'transactions_json' => json_encode($reviewedRows),
            'default_account_id' => $this->account->id,
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'description' => 'Dividen Investasi',
            'amount' => 3000000,
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'description' => 'Belanja Supermarket',
            'amount' => 150000,
            'type' => 'expense',
        ]);
    }
}

