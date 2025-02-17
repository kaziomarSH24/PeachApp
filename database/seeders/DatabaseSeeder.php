<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'role' => 'admin',
            'password' => '12345678',
        ]);
        User::create([
            'first_name' => 'Normal',
            'last_name' => 'User',
            'email' => 'user@gmail.com',
            'email_verified_at' => now(),
            'role' => 'admin',
            'password' => '12345678',
        ]);

        $this->call(UserSeeder::class);
        $this->call(ProfileSeeder::class);


    }
}
