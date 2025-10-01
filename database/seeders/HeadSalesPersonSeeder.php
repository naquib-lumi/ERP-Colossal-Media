<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HeadSalesPersonSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Head Sales Person 1',
            'email' => 'head-sales1@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'head-salesperson',
        ]);

        User::create([
            'name' => 'Head Sales Person 2',
            'email' => 'head-sales2@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'head-salesperson',
        ]);
    }
}