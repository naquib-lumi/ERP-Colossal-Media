<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SalesPersonSeeder extends Seeder
{
    public function run(): void
    {
        // Create multiple salesperson users
        User::create([
            'name' => 'Sales Person 1',
            'email' => 'sales1@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'salesperson',
        ]);

        User::create([
            'name' => 'Sales Person 2',
            'email' => 'sales2@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'salesperson',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }
}