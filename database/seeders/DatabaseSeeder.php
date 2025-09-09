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
        $this->call(ArtistSeeder::class);
        $this->call(OrdersTableSeeder::class);
        $this->call(ProductsTableSeeder::class);
        $this->call(MaterialSeeder::class);
        $this->call(ProductTaskTypeSeeder::class);
        $this->call(OperationSeeder::class);
    }
}