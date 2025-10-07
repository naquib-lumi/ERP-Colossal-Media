<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin One',
            'email' => 'admin1@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'contact_number' => '1234567890',
        ]);

        User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'contact_number' => '0987654321',
        ]);
    }
}