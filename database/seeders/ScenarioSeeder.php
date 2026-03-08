<?php

namespace Database\Seeders;

use App\Domains\Brand\Models\Brand;
use App\Domains\Budget\Models\Budget;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        foreach ($users as $index => $user) {
            // Assign different scenarios based on user index
            $scenarioType = $index % 5;

            match ($scenarioType) {
                0 => $this->seedYoungProfessional($user),
                1 => $this->seedFamilyWithKids($user),
                2 => $this->seedHighEarner($user),
                3 => $this->seedBudgetConscious($user),
                4 => $this->seedEntrepreneur($user),
            };

            $this->command->info("Seeded scenario for: {$user->name}");
        }
    }

    /**
     * Scenario 1: Young Professional - Single, starting career, moderate income
     */
    private function seedYoungProfessional(User $user): void
    {
        $categories = $this->createCategories();
        $brands = $this->createBrandsForYoungProfessional($categories);
        $this->generateTransactions($user, $brands, 'young_professional');
        $this->createBudgetsForYoungProfessional($user, $categories);
    }

    /**
     * Scenario 2: Family with Kids - Higher expenses, education costs
     */
    private function seedFamilyWithKids(User $user): void
    {
        $categories = $this->createCategories();
        $brands = $this->createBrandsForFamily($categories);
        $this->generateTransactions($user, $brands, 'family');
        $this->createBudgetsForFamily($user, $categories);
    }

    /**
     * Scenario 3: High Earner - High income, significant investments
     */
    private function seedHighEarner(User $user): void
    {
        $categories = $this->createCategories();
        $brands = $this->createBrandsForHighEarner($categories);
        $this->generateTransactions($user, $brands, 'high_earner');
        $this->createBudgetsForHighEarner($user, $categories);
    }

    /**
     * Scenario 4: Budget Conscious - Tight budget, tracking every expense
     */
    private function seedBudgetConscious(User $user): void
    {
        $categories = $this->createCategories();
        $brands = $this->createBrandsForBudgetConscious($categories);
        $this->generateTransactions($user, $brands, 'budget_conscious');
        $this->createBudgetsForBudgetConscious($user, $categories);
    }

    /**
     * Scenario 5: Entrepreneur - Variable income, business expenses
     */
    private function seedEntrepreneur(User $user): void
    {
        $categories = $this->createCategories();
        $brands = $this->createBrandsForEntrepreneur($categories);
        $this->generateTransactions($user, $brands, 'entrepreneur');
        $this->createBudgetsForEntrepreneur($user, $categories);
    }

    private function createCategories(): array
    {
        return [
            'income' => Category::firstOrCreate(
                ['name' => 'Income', 'type' => Category::INCOME],
                ['color' => '#22c55e', 'icon' => 'wallet']
            ),
            'housing' => Category::firstOrCreate(
                ['name' => 'Housing', 'type' => Category::EXPENSES],
                ['color' => '#3b82f6', 'icon' => 'home']
            ),
            'groceries' => Category::firstOrCreate(
                ['name' => 'Groceries', 'type' => Category::EXPENSES],
                ['color' => '#10b981', 'icon' => 'shopping-cart']
            ),
            'dining' => Category::firstOrCreate(
                ['name' => 'Dining Out', 'type' => Category::EXPENSES],
                ['color' => '#f59e0b', 'icon' => 'utensils']
            ),
            'transport' => Category::firstOrCreate(
                ['name' => 'Transportation', 'type' => Category::EXPENSES],
                ['color' => '#8b5cf6', 'icon' => 'car']
            ),
            'utilities' => Category::firstOrCreate(
                ['name' => 'Utilities', 'type' => Category::EXPENSES],
                ['color' => '#06b6d4', 'icon' => 'bolt']
            ),
            'shopping' => Category::firstOrCreate(
                ['name' => 'Shopping', 'type' => Category::EXPENSES],
                ['color' => '#ec4899', 'icon' => 'shopping-bag']
            ),
            'entertainment' => Category::firstOrCreate(
                ['name' => 'Entertainment', 'type' => Category::EXPENSES],
                ['color' => '#ef4444', 'icon' => 'film']
            ),
            'healthcare' => Category::firstOrCreate(
                ['name' => 'Healthcare', 'type' => Category::EXPENSES],
                ['color' => '#f43f5e', 'icon' => 'heart']
            ),
            'personal' => Category::firstOrCreate(
                ['name' => 'Personal Care', 'type' => Category::EXPENSES],
                ['color' => '#a855f7', 'icon' => 'user']
            ),
            'education' => Category::firstOrCreate(
                ['name' => 'Education', 'type' => Category::EXPENSES],
                ['color' => '#6366f1', 'icon' => 'book']
            ),
            'savings' => Category::firstOrCreate(
                ['name' => 'Savings', 'type' => Category::SAVINGS],
                ['color' => '#14b8a6', 'icon' => 'piggy-bank']
            ),
            'investment' => Category::firstOrCreate(
                ['name' => 'Investments', 'type' => Category::INVESTMENT],
                ['color' => '#6366f1', 'icon' => 'trending-up']
            ),
        ];
    }

    private function createBrandsForYoungProfessional(array $categories): array
    {
        return [
            'salary' => Brand::firstOrCreate(['name' => 'Monthly Salary'], ['category_id' => $categories['income']->id]),
            'freelance' => Brand::firstOrCreate(['name' => 'Freelance Work'], ['category_id' => $categories['income']->id]),
            'rent' => Brand::firstOrCreate(['name' => 'Apartment Rent'], ['category_id' => $categories['housing']->id]),
            'dewa' => Brand::firstOrCreate(['name' => 'DEWA'], ['category_id' => $categories['housing']->id]),
            'lulu' => Brand::firstOrCreate(['name' => 'Lulu Hypermarket'], ['category_id' => $categories['groceries']->id]),
            'carrefour' => Brand::firstOrCreate(['name' => 'Carrefour'], ['category_id' => $categories['groceries']->id]),
            'starbucks' => Brand::firstOrCreate(['name' => 'Starbucks'], ['category_id' => $categories['dining']->id]),
            'mcdonalds' => Brand::firstOrCreate(['name' => 'McDonalds'], ['category_id' => $categories['dining']->id]),
            'enoc' => Brand::firstOrCreate(['name' => 'ENOC'], ['category_id' => $categories['transport']->id]),
            'rta' => Brand::firstOrCreate(['name' => 'Dubai Metro'], ['category_id' => $categories['transport']->id]),
            'uber' => Brand::firstOrCreate(['name' => 'Uber'], ['category_id' => $categories['transport']->id]),
            'etisalat' => Brand::firstOrCreate(['name' => 'Etisalat'], ['category_id' => $categories['utilities']->id]),
            'amazon' => Brand::firstOrCreate(['name' => 'Amazon.ae'], ['category_id' => $categories['shopping']->id]),
            'netflix' => Brand::firstOrCreate(['name' => 'Netflix'], ['category_id' => $categories['entertainment']->id]),
            'spotify' => Brand::firstOrCreate(['name' => 'Spify'], ['category_id' => $categories['entertainment']->id]),
            'gym' => Brand::firstOrCreate(['name' => 'Fitness First'], ['category_id' => $categories['personal']->id]),
            'pharmacy' => Brand::firstOrCreate(['name' => 'Life Pharmacy'], ['category_id' => $categories['healthcare']->id]),
            'emergency_fund' => Brand::firstOrCreate(['name' => 'Emergency Fund'], ['category_id' => $categories['savings']->id]),
            'stocks' => Brand::firstOrCreate(['name' => 'Stock Investment'], ['category_id' => $categories['investment']->id]),
        ];
    }

    private function createBrandsForFamily(array $categories): array
    {
        return [
            'salary_primary' => Brand::firstOrCreate(['name' => 'Primary Salary'], ['category_id' => $categories['income']->id]),
            'salary_spouse' => Brand::firstOrCreate(['name' => 'Spouse Salary'], ['category_id' => $categories['income']->id]),
            'rent' => Brand::firstOrCreate(['name' => 'Villa Rent'], ['category_id' => $categories['housing']->id]),
            'dewa' => Brand::firstOrCreate(['name' => 'DEWA'], ['category_id' => $categories['housing']->id]),
            'empower' => Brand::firstOrCreate(['name' => 'Empower'], ['category_id' => $categories['housing']->id]),
            'lulu' => Brand::firstOrCreate(['name' => 'Lulu Hypermarket'], ['category_id' => $categories['groceries']->id]),
            'carrefour' => Brand::firstOrCreate(['name' => 'Carrefour'], ['category_id' => $categories['groceries']->id]),
            'union_coop' => Brand::firstOrCreate(['name' => 'Union Coop'], ['category_id' => $categories['groceries']->id]),
            'talabat' => Brand::firstOrCreate(['name' => 'Talabat'], ['category_id' => $categories['dining']->id]),
            'zomato' => Brand::firstOrCreate(['name' => 'Zomato'], ['category_id' => $categories['dining']->id]),
            'enoc' => Brand::firstOrCreate(['name' => 'ENOC'], ['category_id' => $categories['transport']->id]),
            'adnoc' => Brand::firstOrCreate(['name' => 'ADNOC'], ['category_id' => $categories['transport']->id]),
            'salik' => Brand::firstOrCreate(['name' => 'Salik'], ['category_id' => $categories['transport']->id]),
            'etisalat' => Brand::firstOrCreate(['name' => 'Etisalat'], ['category_id' => $categories['utilities']->id]),
            'du' => Brand::firstOrCreate(['name' => 'Du'], ['category_id' => $categories['utilities']->id]),
            'ikea' => Brand::firstOrCreate(['name' => 'IKEA'], ['category_id' => $categories['shopping']->id]),
            'noon' => Brand::firstOrCreate(['name' => 'Noon'], ['category_id' => $categories['shopping']->id]),
            'vox' => Brand::firstOrCreate(['name' => 'VOX Cinemas'], ['category_id' => $categories['entertainment']->id]),
            'dubai_parks' => Brand::firstOrCreate(['name' => 'Dubai Parks'], ['category_id' => $categories['entertainment']->id]),
            'aster' => Brand::firstOrCreate(['name' => 'Aster Clinic'], ['category_id' => $categories['healthcare']->id]),
            'insurance' => Brand::firstOrCreate(['name' => 'Health Insurance'], ['category_id' => $categories['healthcare']->id]),
            'school' => Brand::firstOrCreate(['name' => 'School Fees'], ['category_id' => $categories['education']->id]),
            'tutoring' => Brand::firstOrCreate(['name' => 'Tutoring Center'], ['category_id' => $categories['education']->id]),
            'kids_savings' => Brand::firstOrCreate(['name' => 'Kids Education Fund'], ['category_id' => $categories['savings']->id]),
            'family_savings' => Brand::firstOrCreate(['name' => 'Family Savings'], ['category_id' => $categories['savings']->id]),
        ];
    }

    private function createBrandsForHighEarner(array $categories): array
    {
        return [
            'salary' => Brand::firstOrCreate(['name' => 'Executive Salary'], ['category_id' => $categories['income']->id]),
            'bonus' => Brand::firstOrCreate(['name' => 'Performance Bonus'], ['category_id' => $categories['income']->id]),
            'investment_income' => Brand::firstOrCreate(['name' => 'Investment Returns'], ['category_id' => $categories['income']->id]),
            'mortgage' => Brand::firstOrCreate(['name' => 'Mortgage Payment'], ['category_id' => $categories['housing']->id]),
            'maintenance' => Brand::firstOrCreate(['name' => 'Property Maintenance'], ['category_id' => $categories['housing']->id]),
            'spinneys' => Brand::firstOrCreate(['name' => 'Spinneys'], ['category_id' => $categories['groceries']->id]),
            'waitrose' => Brand::firstOrCreate(['name' => 'Waitrose'], ['category_id' => $categories['groceries']->id]),
            'fine_dining' => Brand::firstOrCreate(['name' => 'Fine Dining'], ['category_id' => $categories['dining']->id]),
            'business_lunch' => Brand::firstOrCreate(['name' => 'Business Meals'], ['category_id' => $categories['dining']->id]),
            'premium_fuel' => Brand::firstOrCreate(['name' => 'Premium Fuel'], ['category_id' => $categories['transport']->id]),
            'car_service' => Brand::firstOrCreate(['name' => 'Luxury Car Service'], ['category_id' => $categories['transport']->id]),
            'business_class' => Brand::firstOrCreate(['name' => 'Business Travel'], ['category_id' => $categories['transport']->id]),
            'premium_utilities' => Brand::firstOrCreate(['name' => 'Premium Utilities'], ['category_id' => $categories['utilities']->id]),
            'luxury_shopping' => Brand::firstOrCreate(['name' => 'Luxury Retail'], ['category_id' => $categories['shopping']->id]),
            'dubai_mall' => Brand::firstOrCreate(['name' => 'Dubai Mall'], ['category_id' => $categories['shopping']->id]),
            'premium_entertainment' => Brand::firstOrCreate(['name' => 'Premium Events'], ['category_id' => $categories['entertainment']->id]),
            'golf' => Brand::firstOrCreate(['name' => 'Golf Club'], ['category_id' => $categories['entertainment']->id]),
            'private_healthcare' => Brand::firstOrCreate(['name' => 'Private Healthcare'], ['category_id' => $categories['healthcare']->id]),
            'premium_gym' => Brand::firstOrCreate(['name' => 'Premium Gym'], ['category_id' => $categories['personal']->id]),
            'spa' => Brand::firstOrCreate(['name' => 'Luxury Spa'], ['category_id' => $categories['personal']->id]),
            'stocks' => Brand::firstOrCreate(['name' => 'Stock Portfolio'], ['category_id' => $categories['investment']->id]),
            'real_estate' => Brand::firstOrCreate(['name' => 'Real Estate Investment'], ['category_id' => $categories['investment']->id]),
            'crypto' => Brand::firstOrCreate(['name' => 'Cryptocurrency'], ['category_id' => $categories['investment']->id]),
            'gold' => Brand::firstOrCreate(['name' => 'Gold Investment'], ['category_id' => $categories['investment']->id]),
            'retirement' => Brand::firstOrCreate(['name' => 'Retirement Fund'], ['category_id' => $categories['savings']->id]),
        ];
    }

    private function createBrandsForBudgetConscious(array $categories): array
    {
        return [
            'salary' => Brand::firstOrCreate(['name' => 'Monthly Salary'], ['category_id' => $categories['income']->id]),
            'part_time' => Brand::firstOrCreate(['name' => 'Part-time Work'], ['category_id' => $categories['income']->id]),
            'shared_rent' => Brand::firstOrCreate(['name' => 'Shared Accommodation'], ['category_id' => $categories['housing']->id]),
            'westzone' => Brand::firstOrCreate(['name' => 'West Zone'], ['category_id' => $categories['groceries']->id]),
            'union_coop' => Brand::firstOrCreate(['name' => 'Union Coop'], ['category_id' => $categories['groceries']->id]),
            'home_cooked' => Brand::firstOrCreate(['name' => 'Home Cooking'], ['category_id' => $categories['groceries']->id]),
            'meal_prep' => Brand::firstOrCreate(['name' => 'Meal Prep'], ['category_id' => $categories['dining']->id]),
            'rta' => Brand::firstOrCreate(['name' => 'Public Transport'], ['category_id' => $categories['transport']->id]),
            'metro' => Brand::firstOrCreate(['name' => 'Dubai Metro'], ['category_id' => $categories['transport']->id]),
            'bus' => Brand::firstOrCreate(['name' => 'Public Bus'], ['category_id' => $categories['transport']->id]),
            'basic_utilities' => Brand::firstOrCreate(['name' => 'Basic Utilities'], ['category_id' => $categories['utilities']->id]),
            'prepaid_mobile' => Brand::firstOrCreate(['name' => 'Prepaid Mobile'], ['category_id' => $categories['utilities']->id]),
            'discount_shopping' => Brand::firstOrCreate(['name' => 'Discount Stores'], ['category_id' => $categories['shopping']->id]),
            'second_hand' => Brand::firstOrCreate(['name' => 'Second Hand'], ['category_id' => $categories['shopping']->id]),
            'free_entertainment' => Brand::firstOrCreate(['name' => 'Free Events'], ['category_id' => $categories['entertainment']->id]),
            'public_parks' => Brand::firstOrCreate(['name' => 'Public Parks'], ['category_id' => $categories['entertainment']->id]),
            'basic_healthcare' => Brand::firstOrCreate(['name' => 'Basic Healthcare'], ['category_id' => $categories['healthcare']->id]),
            'generic_meds' => Brand::firstOrCreate(['name' => 'Generic Medicines'], ['category_id' => $categories['healthcare']->id]),
            'self_care' => Brand::firstOrCreate(['name' => 'Self Care'], ['category_id' => $categories['personal']->id]),
            'emergency_fund' => Brand::firstOrCreate(['name' => 'Emergency Fund'], ['category_id' => $categories['savings']->id]),
            'micro_invest' => Brand::firstOrCreate(['name' => 'Micro Investment'], ['category_id' => $categories['investment']->id]),
        ];
    }

    private function createBrandsForEntrepreneur(array $categories): array
    {
        return [
            'business_revenue' => Brand::firstOrCreate(['name' => 'Business Revenue'], ['category_id' => $categories['income']->id]),
            'client_payment' => Brand::firstOrCreate(['name' => 'Client Payments'], ['category_id' => $categories['income']->id]),
            'consulting' => Brand::firstOrCreate(['name' => 'Consulting Fees'], ['category_id' => $categories['income']->id]),
            'office_rent' => Brand::firstOrCreate(['name' => 'Office Rent'], ['category_id' => $categories['housing']->id]),
            'home_office' => Brand::firstOrCreate(['name' => 'Home Office'], ['category_id' => $categories['housing']->id]),
            'office_groceries' => Brand::firstOrCreate(['name' => 'Office Supplies & Food'], ['category_id' => $categories['groceries']->id]),
            'business_meals' => Brand::firstOrCreate(['name' => 'Business Meals'], ['category_id' => $categories['dining']->id]),
            'client_entertainment' => Brand::firstOrCreate(['name' => 'Client Entertainment'], ['category_id' => $categories['dining']->id]),
            'business_travel' => Brand::firstOrCreate(['name' => 'Business Travel'], ['category_id' => $categories['transport']->id]),
            'company_car' => Brand::firstOrCreate(['name' => 'Company Car'], ['category_id' => $categories['transport']->id]),
            'internet' => Brand::firstOrCreate(['name' => 'Business Internet'], ['category_id' => $categories['utilities']->id]),
            'software' => Brand::firstOrCreate(['name' => 'Software Subscriptions'], ['category_id' => $categories['utilities']->id]),
            'equipment' => Brand::firstOrCreate(['name' => 'Equipment'], ['category_id' => $categories['shopping']->id]),
            'tech_upgrades' => Brand::firstOrCreate(['name' => 'Tech Upgrades'], ['category_id' => $categories['shopping']->id]),
            'networking_events' => Brand::firstOrCreate(['name' => 'Networking Events'], ['category_id' => $categories['entertainment']->id]),
            'conferences' => Brand::firstOrCreate(['name' => 'Conferences'], ['category_id' => $categories['entertainment']->id]),
            'business_insurance' => Brand::firstOrCreate(['name' => 'Business Insurance'], ['category_id' => $categories['healthcare']->id]),
            'professional_dev' => Brand::firstOrCreate(['name' => 'Professional Development'], ['category_id' => $categories['education']->id]),
            'courses' => Brand::firstOrCreate(['name' => 'Online Courses'], ['category_id' => $categories['education']->id]),
            'business_savings' => Brand::firstOrCreate(['name' => 'Business Savings'], ['category_id' => $categories['savings']->id]),
            'tax_reserve' => Brand::firstOrCreate(['name' => 'Tax Reserve'], ['category_id' => $categories['savings']->id]),
            'business_investment' => Brand::firstOrCreate(['name' => 'Business Investment'], ['category_id' => $categories['investment']->id]),
            'portfolio' => Brand::firstOrCreate(['name' => 'Investment Portfolio'], ['category_id' => $categories['investment']->id]),
        ];
    }

    private function generateTransactions(User $user, array $brands, string $scenario): void
    {
        $transactionConfigs = match ($scenario) {
            'young_professional' => $this->getYoungProfessionalTransactions(),
            'family' => $this->getFamilyTransactions(),
            'high_earner' => $this->getHighEarnerTransactions(),
            'budget_conscious' => $this->getBudgetConsciousTransactions(),
            'entrepreneur' => $this->getEntrepreneurTransactions(),
        };

        // Generate transactions for the past 6 months
        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $monthStart = Carbon::now()->subMonths($monthsAgo)->startOfMonth();
            
            foreach ($transactionConfigs as $config) {
                $this->createTransactionForConfig($user, $brands, $config, $monthStart);
            }
        }
    }

    private function getYoungProfessionalTransactions(): array
    {
        return [
            ['brand' => 'salary', 'amount' => [12000, 12000], 'day' => 1, 'note' => 'Monthly salary'],
            ['brand' => 'freelance', 'amount' => [500, 2000], 'day' => [5, 25], 'note' => 'Freelance project', 'probability' => 0.4],
            ['brand' => 'rent', 'amount' => [3500, 3500], 'day' => 5, 'note' => 'Apartment rent'],
            ['brand' => 'dewa', 'amount' => [250, 400], 'day' => 10, 'note' => 'Electricity & water'],
            ['brand' => 'lulu', 'amount' => [200, 400], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Grocery shopping'],
            ['brand' => 'carrefour', 'amount' => [100, 250], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Quick grocery run'],
            ['brand' => 'starbucks', 'amount' => [20, 45], 'day' => [1, 28], 'frequency' => 12, 'note' => 'Coffee'],
            ['brand' => 'mcdonalds', 'amount' => [30, 60], 'day' => [1, 28], 'frequency' => 6, 'note' => 'Fast food'],
            ['brand' => 'enoc', 'amount' => [120, 200], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Petrol'],
            ['brand' => 'uber', 'amount' => [25, 60], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Ride sharing'],
            ['brand' => 'rta', 'amount' => [15, 30], 'day' => [1, 28], 'frequency' => 8, 'note' => 'Metro'],
            ['brand' => 'etisalat', 'amount' => [250, 350], 'day' => 12, 'note' => 'Mobile & internet'],
            ['brand' => 'amazon', 'amount' => [100, 500], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Online shopping'],
            ['brand' => 'netflix', 'amount' => [56, 56], 'day' => 5, 'note' => 'Subscription'],
            ['brand' => 'spotify', 'amount' => [20, 20], 'day' => 5, 'note' => 'Subscription'],
            ['brand' => 'gym', 'amount' => [200, 300], 'day' => 1, 'note' => 'Gym membership'],
            ['brand' => 'pharmacy', 'amount' => [50, 150], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Medicines', 'probability' => 0.6],
            ['brand' => 'emergency_fund', 'amount' => [500, 800], 'day' => 10, 'note' => 'Monthly savings'],
            ['brand' => 'stocks', 'amount' => [300, 600], 'day' => 15, 'note' => 'Investment', 'probability' => 0.7],
        ];
    }

    private function getFamilyTransactions(): array
    {
        return [
            ['brand' => 'salary_primary', 'amount' => [18000, 18000], 'day' => 1, 'note' => 'Primary income'],
            ['brand' => 'salary_spouse', 'amount' => [10000, 10000], 'day' => 1, 'note' => 'Secondary income'],
            ['brand' => 'rent', 'amount' => [8000, 8000], 'day' => 5, 'note' => 'Villa rent'],
            ['brand' => 'dewa', 'amount' => [600, 900], 'day' => 10, 'note' => 'Electricity & water'],
            ['brand' => 'empower', 'amount' => [700, 1000], 'day' => 15, 'note' => 'Chiller'],
            ['brand' => 'lulu', 'amount' => [400, 700], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Weekly groceries'],
            ['brand' => 'carrefour', 'amount' => [300, 500], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Grocery run'],
            ['brand' => 'union_coop', 'amount' => [200, 400], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Bulk shopping'],
            ['brand' => 'talabat', 'amount' => [60, 120], 'day' => [1, 28], 'frequency' => 6, 'note' => 'Food delivery'],
            ['brand' => 'zomato', 'amount' => [50, 100], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Food delivery'],
            ['brand' => 'enoc', 'amount' => [200, 350], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Petrol'],
            ['brand' => 'adnoc', 'amount' => [180, 300], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Petrol'],
            ['brand' => 'salik', 'amount' => [4, 4], 'day' => [1, 28], 'frequency' => 20, 'note' => 'Toll gates'],
            ['brand' => 'etisalat', 'amount' => [400, 500], 'day' => 12, 'note' => 'Family mobile plans'],
            ['brand' => 'du', 'amount' => [150, 200], 'day' => 12, 'note' => 'Secondary mobile'],
            ['brand' => 'ikea', 'amount' => [300, 800], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Home items', 'probability' => 0.5],
            ['brand' => 'noon', 'amount' => [200, 600], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Online shopping'],
            ['brand' => 'vox', 'amount' => [120, 250], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Movie night'],
            ['brand' => 'dubai_parks', 'amount' => [300, 600], 'day' => [15, 25], 'frequency' => 1, 'note' => 'Family outing', 'probability' => 0.4],
            ['brand' => 'aster', 'amount' => [100, 300], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Clinic visits'],
            ['brand' => 'insurance', 'amount' => [1500, 1500], 'day' => 5, 'note' => 'Health insurance', 'probability' => 0.25],
            ['brand' => 'school', 'amount' => [4000, 4000], 'day' => 5, 'note' => 'School fees', 'probability' => 0.33],
            ['brand' => 'tutoring', 'amount' => [800, 1200], 'day' => 5, 'note' => 'Tutoring'],
            ['brand' => 'kids_savings', 'amount' => [1000, 1500], 'day' => 10, 'note' => 'Education fund'],
            ['brand' => 'family_savings', 'amount' => [2000, 3000], 'day' => 10, 'note' => 'Monthly savings'],
        ];
    }

    private function getHighEarnerTransactions(): array
    {
        return [
            ['brand' => 'salary', 'amount' => [50000, 50000], 'day' => 1, 'note' => 'Executive salary'],
            ['brand' => 'bonus', 'amount' => [25000, 50000], 'day' => 15, 'note' => 'Quarterly bonus', 'probability' => 0.25],
            ['brand' => 'investment_income', 'amount' => [2000, 8000], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Investment returns', 'probability' => 0.5],
            ['brand' => 'mortgage', 'amount' => [15000, 15000], 'day' => 1, 'note' => 'Mortgage payment'],
            ['brand' => 'maintenance', 'amount' => [1000, 3000], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Property maintenance'],
            ['brand' => 'spinneys', 'amount' => [800, 1500], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Premium groceries'],
            ['brand' => 'waitrose', 'amount' => [600, 1200], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Gourmet shopping'],
            ['brand' => 'fine_dining', 'amount' => [400, 1000], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Fine dining'],
            ['brand' => 'business_lunch', 'amount' => [200, 500], 'day' => [1, 28], 'frequency' => 6, 'note' => 'Business meals'],
            ['brand' => 'premium_fuel', 'amount' => [300, 500], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Premium petrol'],
            ['brand' => 'car_service', 'amount' => [2000, 5000], 'day' => [10, 20], 'frequency' => 1, 'note' => 'Luxury car service', 'probability' => 0.3],
            ['brand' => 'business_class', 'amount' => [5000, 15000], 'day' => [5, 25], 'frequency' => 1, 'note' => 'Business travel', 'probability' => 0.4],
            ['brand' => 'premium_utilities', 'amount' => [800, 1200], 'day' => 10, 'note' => 'Utilities'],
            ['brand' => 'luxury_shopping', 'amount' => [2000, 10000], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Luxury retail'],
            ['brand' => 'dubai_mall', 'amount' => [1500, 5000], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Shopping'],
            ['brand' => 'premium_entertainment', 'amount' => [1000, 3000], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Premium events'],
            ['brand' => 'golf', 'amount' => [800, 1500], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Golf club'],
            ['brand' => 'private_healthcare', 'amount' => [1000, 5000], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Private healthcare'],
            ['brand' => 'premium_gym', 'amount' => [800, 1500], 'day' => 1, 'note' => 'Premium gym'],
            ['brand' => 'spa', 'amount' => [500, 1500], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Luxury spa'],
            ['brand' => 'stocks', 'amount' => [5000, 15000], 'day' => 10, 'note' => 'Stock investment'],
            ['brand' => 'real_estate', 'amount' => [20000, 50000], 'day' => 15, 'note' => 'Property investment', 'probability' => 0.2],
            ['brand' => 'crypto', 'amount' => [2000, 10000], 'day' => 20, 'note' => 'Crypto investment', 'probability' => 0.4],
            ['brand' => 'gold', 'amount' => [5000, 20000], 'day' => [5, 25], 'frequency' => 1, 'note' => 'Gold purchase', 'probability' => 0.25],
            ['brand' => 'retirement', 'amount' => [5000, 10000], 'day' => 10, 'note' => 'Retirement savings'],
        ];
    }

    private function getBudgetConsciousTransactions(): array
    {
        return [
            ['brand' => 'salary', 'amount' => [7000, 7000], 'day' => 1, 'note' => 'Monthly salary'],
            ['brand' => 'part_time', 'amount' => [1000, 2000], 'day' => 15, 'note' => 'Part-time work', 'probability' => 0.6],
            ['brand' => 'shared_rent', 'amount' => [2000, 2000], 'day' => 5, 'note' => 'Shared rent'],
            ['brand' => 'westzone', 'amount' => [150, 300], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Budget groceries'],
            ['brand' => 'union_coop', 'amount' => [100, 200], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Bulk shopping'],
            ['brand' => 'home_cooked', 'amount' => [50, 150], 'day' => [1, 28], 'frequency' => 10, 'note' => 'Home cooking'],
            ['brand' => 'meal_prep', 'amount' => [30, 80], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Meal prep'],
            ['brand' => 'rta', 'amount' => [10, 25], 'day' => [1, 28], 'frequency' => 15, 'note' => 'Public transport'],
            ['brand' => 'metro', 'amount' => [6, 15], 'day' => [1, 28], 'frequency' => 12, 'note' => 'Metro'],
            ['brand' => 'bus', 'amount' => [4, 10], 'day' => [1, 28], 'frequency' => 8, 'note' => 'Bus'],
            ['brand' => 'basic_utilities', 'amount' => [150, 250], 'day' => 10, 'note' => 'Utilities'],
            ['brand' => 'prepaid_mobile', 'amount' => [50, 100], 'day' => 5, 'note' => 'Mobile credit'],
            ['brand' => 'discount_shopping', 'amount' => [50, 200], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Discount shopping'],
            ['brand' => 'second_hand', 'amount' => [30, 150], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Second hand items', 'probability' => 0.5],
            ['brand' => 'free_entertainment', 'amount' => [20, 50], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Events'],
            ['brand' => 'public_parks', 'amount' => [10, 30], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Park visits'],
            ['brand' => 'basic_healthcare', 'amount' => [50, 150], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Healthcare'],
            ['brand' => 'generic_meds', 'amount' => [20, 80], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Medicines', 'probability' => 0.7],
            ['brand' => 'self_care', 'amount' => [30, 80], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Self care'],
            ['brand' => 'emergency_fund', 'amount' => [200, 400], 'day' => 10, 'note' => 'Emergency savings'],
            ['brand' => 'micro_invest', 'amount' => [100, 300], 'day' => 15, 'note' => 'Micro investment', 'probability' => 0.5],
        ];
    }

    private function getEntrepreneurTransactions(): array
    {
        return [
            ['brand' => 'business_revenue', 'amount' => [15000, 30000], 'day' => 1, 'note' => 'Business revenue', 'probability' => 0.8],
            ['brand' => 'client_payment', 'amount' => [5000, 15000], 'day' => [5, 25], 'frequency' => 2, 'note' => 'Client payment'],
            ['brand' => 'consulting', 'amount' => [3000, 8000], 'day' => [10, 20], 'frequency' => 1, 'note' => 'Consulting', 'probability' => 0.5],
            ['brand' => 'office_rent', 'amount' => [4000, 4000], 'day' => 5, 'note' => 'Office rent', 'probability' => 0.5],
            ['brand' => 'home_office', 'amount' => [1500, 1500], 'day' => 5, 'note' => 'Home office expenses'],
            ['brand' => 'office_groceries', 'amount' => [300, 600], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Office supplies & food'],
            ['brand' => 'business_meals', 'amount' => [100, 300], 'day' => [1, 28], 'frequency' => 8, 'note' => 'Business meals'],
            ['brand' => 'client_entertainment', 'amount' => [300, 800], 'day' => [1, 28], 'frequency' => 3, 'note' => 'Client entertainment'],
            ['brand' => 'business_travel', 'amount' => [1000, 5000], 'day' => [5, 25], 'frequency' => 1, 'note' => 'Business travel', 'probability' => 0.4],
            ['brand' => 'company_car', 'amount' => [200, 400], 'day' => [1, 28], 'frequency' => 4, 'note' => 'Company car fuel'],
            ['brand' => 'internet', 'amount' => [500, 800], 'day' => 10, 'note' => 'Business internet'],
            ['brand' => 'software', 'amount' => [200, 1000], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Software subscriptions'],
            ['brand' => 'equipment', 'amount' => [500, 3000], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Equipment', 'probability' => 0.3],
            ['brand' => 'tech_upgrades', 'amount' => [1000, 5000], 'day' => [10, 25], 'frequency' => 1, 'note' => 'Tech upgrades', 'probability' => 0.2],
            ['brand' => 'networking_events', 'amount' => [200, 500], 'day' => [1, 28], 'frequency' => 2, 'note' => 'Networking'],
            ['brand' => 'conferences', 'amount' => [1000, 5000], 'day' => [5, 20], 'frequency' => 1, 'note' => 'Conferences', 'probability' => 0.25],
            ['brand' => 'business_insurance', 'amount' => [800, 1500], 'day' => 5, 'note' => 'Insurance'],
            ['brand' => 'professional_dev', 'amount' => [500, 2000], 'day' => 15, 'note' => 'Professional development'],
            ['brand' => 'courses', 'amount' => [100, 500], 'day' => [1, 28], 'frequency' => 1, 'note' => 'Online courses'],
            ['brand' => 'business_savings', 'amount' => [2000, 5000], 'day' => 10, 'note' => 'Business savings'],
            ['brand' => 'tax_reserve', 'amount' => [3000, 6000], 'day' => 10, 'note' => 'Tax reserve'],
            ['brand' => 'business_investment', 'amount' => [5000, 15000], 'day' => 15, 'note' => 'Business expansion', 'probability' => 0.3],
            ['brand' => 'portfolio', 'amount' => [3000, 8000], 'day' => 20, 'note' => 'Investment'],
        ];
    }

    private function createTransactionForConfig(User $user, array $brands, array $config, Carbon $monthStart): void
    {
        $brandKey = $config['brand'];
        
        if (!isset($brands[$brandKey])) {
            return;
        }

        $brand = $brands[$brandKey];
        $frequency = $config['frequency'] ?? 1;
        $probability = $config['probability'] ?? 1.0;

        for ($i = 0; $i < $frequency; $i++) {
            if (mt_rand() / mt_getrandmax() > $probability) {
                continue;
            }

            $amount = is_array($config['amount']) 
                ? mt_rand($config['amount'][0] * 100, $config['amount'][1] * 100) / 100 
                : $config['amount'];

            $day = is_array($config['day']) 
                ? mt_rand($config['day'][0], min($config['day'][1], $monthStart->daysInMonth)) 
                : min($config['day'], $monthStart->daysInMonth);

            $date = $monthStart->copy()->addDays($day - 1);

            Transaction::create([
                'brand_id' => $brand->id,
                'amount' => $amount,
                'note' => $config['note'] ?? null,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }

    private function createBudgetsForYoungProfessional(User $user, array $categories): void
    {
        $this->createBudget('Monthly Expenses', 8000, $categories['housing'], Budget::MONTHLY);
        $this->createBudget('Groceries', 2000, $categories['groceries'], Budget::MONTHLY);
        $this->createBudget('Entertainment', 1000, $categories['entertainment'], Budget::MONTHLY);
        $this->createBudget('Transport', 1200, $categories['transport'], Budget::MONTHLY);
    }

    private function createBudgetsForFamily(User $user, array $categories): void
    {
        $this->createBudget('Family Expenses', 20000, $categories['housing'], Budget::MONTHLY);
        $this->createBudget('Family Groceries', 5000, $categories['groceries'], Budget::MONTHLY);
        $this->createBudget('Kids Education', 5000, $categories['education'], Budget::MONTHLY);
        $this->createBudget('Family Entertainment', 2000, $categories['entertainment'], Budget::MONTHLY);
    }

    private function createBudgetsForHighEarner(User $user, array $categories): void
    {
        $this->createBudget('Luxury Living', 40000, $categories['housing'], Budget::MONTHLY);
        $this->createBudget('Premium Lifestyle', 15000, $categories['shopping'], Budget::MONTHLY);
        $this->createBudget('Entertainment', 8000, $categories['entertainment'], Budget::MONTHLY);
        $this->createBudget('Investment Budget', 50000, $categories['investment'], Budget::MONTHLY);
    }

    private function createBudgetsForBudgetConscious(User $user, array $categories): void
    {
        $this->createBudget('Essential Expenses', 5000, $categories['housing'], Budget::MONTHLY);
        $this->createBudget('Food Budget', 1500, $categories['groceries'], Budget::MONTHLY);
        $this->createBudget('Transport', 800, $categories['transport'], Budget::MONTHLY);
        $this->createBudget('Savings Goal', 1000, $categories['savings'], Budget::MONTHLY);
    }

    private function createBudgetsForEntrepreneur(User $user, array $categories): void
    {
        $this->createBudget('Business Operations', 15000, $categories['housing'], Budget::MONTHLY);
        $this->createBudget('Growth Investment', 30000, $categories['investment'], Budget::MONTHLY);
        $this->createBudget('Business Development', 8000, $categories['education'], Budget::MONTHLY);
        $this->createBudget('Networking & Marketing', 5000, $categories['entertainment'], Budget::MONTHLY);
    }

    private function createBudget(string $name, float $amount, Category $category, string $reoccurrence): void
    {
        $startDate = Carbon::now()->startOfMonth();
        
        Budget::firstOrCreate(
            [
                'name' => $name,
                'start_at' => $startDate,
            ],
            [
                'amount' => $amount,
                'end_at' => $startDate->copy()->endOfMonth(),
                'reoccurrence' => $reoccurrence,
                'period' => 1,
                'saving' => false,
            ]
        )->categories()->syncWithoutDetaching([$category->id]);
    }
}
