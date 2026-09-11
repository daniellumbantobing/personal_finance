<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Salary', 'type' => 'income', 'icon' => '💵'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => '💻'],
            ['name' => 'Bonus', 'type' => 'income', 'icon' => '🎁'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => '💰'],
            ['name' => 'Food', 'type' => 'expense', 'icon' => '🍔'],
            ['name' => 'Transportation', 'type' => 'expense', 'icon' => '🚗'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => '🛒'],
            ['name' => 'Bills', 'type' => 'expense', 'icon' => '🧾'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => '🎬'],
            ['name' => 'Health', 'type' => 'expense', 'icon' => '🏥'],
            ['name' => 'Education', 'type' => 'expense', 'icon' => '📚'],
            ['name' => 'Other', 'type' => 'expense', 'icon' => '📦'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate([
                'name' => $category['name'],
                'type' => $category['type'],
            ], [
                'icon' => $category['icon'],
                'is_default' => true,
            ]);
        }
    }
}
