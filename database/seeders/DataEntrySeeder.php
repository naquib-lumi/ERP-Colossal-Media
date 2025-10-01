<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DataEntrySeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Data Entry 1',
            'email' => 'data-entry1@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'data-entry',
        ]);

        User::create([
            'name' => 'Data Entry 2',
            'email' => 'data-entry2@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'data-entry',
        ]);

        User::create([
            'name' => 'Data Entry 3',
            'email' => 'data-entry3@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'data-entry',
        ]);
    }
}