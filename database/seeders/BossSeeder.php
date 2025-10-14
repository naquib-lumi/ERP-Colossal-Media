<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BossSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Boss ERP',
            'email' => 'boss@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'boss',
        ]);
    }
}