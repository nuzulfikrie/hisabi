<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Domains\Brand\Models\Brand;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class SpendingCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Create spending categories with colors matching the image
        $categories = [
            ['name' => 'Utilities', 'color' => '#3b82f6', 'type' => 'EXPENSES'],     // Blue
            ['name' => 'Groceries', 'color' => '#4a7c59', 'type' => 'EXPENSES'],     // Green
            ['name' => 'Transport', 'color' => '#f59e0b', 'type' => 'EXPENSES'],     // Orange
            ['name' => 'Dining', 'color' => '#dc2626', 'type' => 'EXPENSES'],       // Red
            ['name' => 'Entertainment', 'color' => '#8b5cf6', 'type' => 'EXPENSES'], // Purple
            ['name' => 'Health', 'color' => '#14b8a6', 'type' => 'EXPENSES'],       // Teal
            ['name' => 'Household', 'color' => '#374151', 'type' => 'EXPENSES'],     // Dark gray
        ];

        foreach ($categories as $catData) {
            Category::firstOrCreate(
                ['name' => $catData['name']],
                $catData
            );
        }

        // Create brands for each category
        $brands = [
            'Utilities' => ['Electric Company', 'Water Dept', 'Internet Provider'],
            'Groceries' => ['SuperMart', 'Fresh Foods', 'Grocery Store'],
            'Transport' => ['Gas Station', 'Bus Pass', 'Ride Share'],
            'Dining' => ['Coffee Shop', 'Restaurant', 'Fast Food'],
            'Entertainment' => ['Netflix', 'Cinema', 'Streaming Service'],
            'Health' => ['Pharmacy', 'Doctor', 'Gym'],
            'Household' => ['Cleaning Supplies', 'Home Depot', 'Furniture'],
        ];

        foreach ($brands as $categoryName => $brandNames) {
            $category = Category::where('name', $categoryName)->first();
            if ($category) {
                foreach ($brandNames as $brandName) {
                    Brand::firstOrCreate(
                        ['name' => $brandName],
                        ['category_id' => $category->id]
                    );
                }
            }
        }

        // Create demo transactions matching the image data
        $transactions = [
            // Home transactions
            ['description' => 'Internet', 'brand' => 'Internet Provider', 'category' => 'Utilities', 'type' => 'home', 'amount' => 60.00, 'date' => Carbon::now()->subDays(2)],
            ['description' => 'Groceries midweek', 'brand' => 'SuperMart', 'category' => 'Groceries', 'type' => 'home', 'amount' => 42.30, 'date' => Carbon::now()->subDays(3)],
            ['description' => 'Water bill', 'brand' => 'Water Dept', 'category' => 'Utilities', 'type' => 'home', 'amount' => 45.00, 'date' => Carbon::now()->subDays(6)],
            ['description' => 'Cleaning supplies', 'brand' => 'Cleaning Supplies', 'category' => 'Household', 'type' => 'home', 'amount' => 32.00, 'date' => Carbon::now()->subDays(8)],
            ['description' => 'Electric bill', 'brand' => 'Electric Company', 'category' => 'Utilities', 'type' => 'home', 'amount' => 120.00, 'date' => Carbon::now()->subDays(14)],
            
            // Personal transactions
            ['description' => 'Gas', 'brand' => 'Gas Station', 'category' => 'Transport', 'type' => 'personal', 'amount' => 55.00, 'date' => Carbon::now()->subDays(5)],
            ['description' => 'Dinner out', 'brand' => 'Restaurant', 'category' => 'Dining', 'type' => 'personal', 'amount' => 65.00, 'date' => Carbon::now()->subDays(7)],
            ['description' => 'Pharmacy', 'brand' => 'Pharmacy', 'category' => 'Health', 'type' => 'personal', 'amount' => 28.00, 'date' => Carbon::now()->subDays(9)],
            ['description' => 'Netflix', 'brand' => 'Netflix', 'category' => 'Entertainment', 'type' => 'personal', 'amount' => 15.99, 'date' => Carbon::now()->subDays(10)],
            ['description' => 'Coffee shop', 'brand' => 'Coffee Shop', 'category' => 'Dining', 'type' => 'personal', 'amount' => 12.50, 'date' => Carbon::now()->subDays(11)],
            ['description' => 'Bus pass', 'brand' => 'Bus Pass', 'category' => 'Transport', 'type' => 'personal', 'amount' => 45.00, 'date' => Carbon::now()->subDays(12)],
        ];

        foreach ($transactions as $transData) {
            $category = Category::where('name', $transData['category'])->first();
            $brand = Brand::where('name', $transData['brand'])->where('category_id', $category?->id)->first();
            
            if ($brand) {
                Transaction::firstOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'amount' => $transData['amount'],
                        'created_at' => $transData['date'],
                    ],
                    [
                        'description' => $transData['description'],
                        'type' => $transData['type'],
                        'user_id' => 1, // Default user
                    ]
                );
            }
        }

        $this->command->info('Spending categories, brands, and demo transactions created successfully!');
    }
}
