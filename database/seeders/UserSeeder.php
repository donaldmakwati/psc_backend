<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Services\StaffIdGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist before creating users
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            // Create 4 admin users
            for ($i = 1; $i <= 4; $i++) {
                $user = User::create([
                    'name'     => "Admin {$i}",
                     'surname'     => "PSC {$i}",
                      'phone'     => "+263 716 234 64{$i}",
                       'address'     => "5772 6th Close Glen view{$i}",
                    'email'    => "admin{$i}@example.com",
                    'password' => Hash::make('password'), // Use a strong password in production!
                    // Do NOT set staff_id here initially, let the update handle it
                ]);

                // Attach the admin role to the user
                $user->roles()->attach($adminRole);

                // --- NEW PART: Generate and update staff_id AFTER role is attached ---
                $user->staff_id = StaffIdGenerator::generateId('admin'); // Pass the role name directly
                $user->save(); // Save the user with the generated staff_id
                // -------------------------------------------------------------------

                $this->command->info("Created Admin User: {$user->name} with ID: {$user->staff_id}");
            }
        } else {
            $this->command->error("Admin role not found. Please run RoleSeeder first.");
        }

        // For your default test user, you can also assign a role and generate ID if desired
        $defaultTestUser = User::factory()->create([
            'name' => 'PSC',
            'email' => 'admin_psc@example.com',
        ]);
        // If you want this user to also have a generated staff_id based on a role:
        // $staffRole = Role::where('name', 'staff')->first(); // Or 'admin'
        // if ($staffRole) {
        //     $defaultTestUser->roles()->attach($staffRole);
        //     $defaultTestUser->staff_id = StaffIdGenerator::generateId('staff'); // Use 'staff' or 'admin'
        //     $defaultTestUser->save();
        //     $this->command->info("Created Default Test User with ID: {$defaultTestUser->staff_id}");
        // }
    }
}