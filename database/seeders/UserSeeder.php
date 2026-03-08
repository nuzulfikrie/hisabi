<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ahmad Hassan',
                'email' => 'ahmad.hassan@example.com',
            ],
            [
                'name' => 'Fatima Al-Rashid',
                'email' => 'fatima.alrashid@example.com',
            ],
            [
                'name' => 'Omar Khalid',
                'email' => 'omar.khalid@example.com',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
            ],
            [
                'name' => 'Mohammed Al-Farsi',
                'email' => 'mohammad.alfarsi@example.com',
            ],
            [
                'name' => 'Emma Wilson',
                'email' => 'emma.wilson@example.com',
            ],
            [
                'name' => 'Khalid Al-Mansouri',
                'email' => 'khalid.mansouri@example.com',
            ],
            [
                'name' => 'Layla Ibrahim',
                'email' => 'layla.ibrahim@example.com',
            ],
            [
                'name' => 'James Anderson',
                'email' => 'james.anderson@example.com',
            ],
            [
                'name' => 'Noor Al-Hashimi',
                'email' => 'noor.hashimi@example.com',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info("User created/verified: {$user->name} ({$user->email})");
        }

        $this->command->info('All users have password: password');
    }
}
