<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OperationSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Printer A',
            'email' => 'printer@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'operations-printing',
        ]);

        User::create([
            'name' => 'Furnishing A',
            'email' => 'furnishing@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'operations-manager',
        ]);

        User::create([
            'name' => 'Dispatch Control A',
            'email' => 'dispatch-control@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'operations-delivery',
        ]);

        User::create([
            'name' => 'Delivery Installation A',
            'email' => 'delivery-installation@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'operations-installation',
        ]);
    }
}