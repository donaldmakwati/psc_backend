<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator role']);
        Role::firstOrCreate(['name' => 'staff'], ['description' => 'General staff role']);
        Role::firstOrCreate(['name' => 'operator'], ['description' => 'Operator role']);
    }
}