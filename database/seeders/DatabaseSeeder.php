<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('============================================');
        $this->command->info('  HISABI DATABASE SEEDER');
        $this->command->info('============================================');

        // Step 1: Create admin user
        $this->command->info('\n📝 Creating admin user...');
        $this->call(AdminSeeder::class);

        // Step 2: Create regular users
        $this->command->info('\n👥 Creating 10 regular users...');
        $this->call(UserSeeder::class);

        // Step 3: Create categories and basic brands
        $this->command->info('\n📂 Creating categories and brands...');
        $this->call(CategoryBrandSeeder::class);

        // Step 4: Create scenario-based financial data
        $this->command->info('\n💰 Creating scenario-based financial data...');
        $this->call(ScenarioSeeder::class);

        $this->command->info('\n============================================');
        $this->command->info('  SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('============================================');
        $this->command->info('\n📧 Default login credentials:');
        $this->command->info('   Admin: admin@hisabi.com / password');
        $this->command->info('   Users: [user-email]@example.com / password');
    }
}
