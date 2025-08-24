<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class); // UserSeeder will create its own 'default_test@example.com' user

        // Use firstOrCreate to prevent duplicates if the user already exists
        User::firstOrCreate(
            ['email' => 'admin_psc@example.com'], // Search criteria
            [                                       // Attributes to create if not found
                'name' => 'PSC',
                'password' => \Hash::make('password'), // Add a password
            ]
        );
    }
}