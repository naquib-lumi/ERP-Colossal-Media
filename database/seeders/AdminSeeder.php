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
            'name' => 'Admin Colossal',
            'email' => 'admin@colossal360.com.my',
            'password' => Hash::make('Smart_2025'),
            'role' => 'admin',
            'contact_number' => '01117522414',
        ]);

    }
}