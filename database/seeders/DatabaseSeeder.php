<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SalesPersonSeeder::class);
        $this->call(LeadSeeder::class);
        $this->call(MeetingSeeder::class);
    }
}