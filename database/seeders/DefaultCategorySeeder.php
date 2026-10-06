<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($userId = null): void
    {
        $categories = [
            // Incomes
            ['name' => 'Salary', 'type' => CategoryType::INCOME, 'icon' => 'banknote', 'color' => '#10B981'],
            ['name' => 'Bonus', 'type' => CategoryType::INCOME, 'icon' => 'gift', 'color' => '#34D399'],
            ['name' => 'Investment', 'type' => CategoryType::INCOME, 'icon' => 'trending-up', 'color' => '#059669'],
            ['name' => 'Other Income', 'type' => CategoryType::INCOME, 'icon' => 'plus-circle', 'color' => '#6EE7B7'],

            // Expenses
            ['name' => 'Food & Dining', 'type' => CategoryType::EXPENSE, 'icon' => 'utensils', 'color' => '#F87171'],
            ['name' => 'Transportation', 'type' => CategoryType::EXPENSE, 'icon' => 'car', 'color' => '#FBBF24'],
            ['name' => 'Shopping', 'type' => CategoryType::EXPENSE, 'icon' => 'shopping-bag', 'color' => '#A78BFA'],
            ['name' => 'Bills & Utilities', 'type' => CategoryType::EXPENSE, 'icon' => 'zap', 'color' => '#60A5FA'],
            ['name' => 'Entertainment', 'type' => CategoryType::EXPENSE, 'icon' => 'tv', 'color' => '#F472B6'],
            ['name' => 'Health & Fitness', 'type' => CategoryType::EXPENSE, 'icon' => 'heart', 'color' => '#34D399'],
            ['name' => 'Education', 'type' => CategoryType::EXPENSE, 'icon' => 'book', 'color' => '#818CF8'],
            ['name' => 'Personal Care', 'type' => CategoryType::EXPENSE, 'icon' => 'smile', 'color' => '#FBBF24'],
            ['name' => 'Other Expense', 'type' => CategoryType::EXPENSE, 'icon' => 'minus-circle', 'color' => '#9CA3AF'],
        ];

        // If a user ID is passed, we assign these defaults to them.
        // Otherwise, this can just act as a template or throw an error.
        if ($userId) {
            foreach ($categories as $index => $cat) {
                Category::create([
                    'user_id' => $userId,
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_system' => true, // Default categories are marked as system
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
