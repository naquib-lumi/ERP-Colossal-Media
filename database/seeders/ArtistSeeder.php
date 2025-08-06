<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Head Artist 1',
            'email' => 'headartist@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'head-artist',
        ]);

        User::create([
            'name' => 'Artist 2',
            'email' => 'artist2@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'artist',
        ]);

        User::create([
            'name' => 'Artist 3',
            'email' => 'artist3@colossal360.com.my',
            'password' => Hash::make('password123'),
            'role' => 'artist',
        ]);
    }
}