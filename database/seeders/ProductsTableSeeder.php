<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Assuming you have at least 1 OrderID in 'orders' table
        $orderIds = DB::table('orders')->pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            DB::table('products')->insert([
                'OrderID'       => $faker->randomElement($orderIds),
                'productName'   => $faker->word() . ' ' . $faker->word(),
                'totalQuantity' => $faker->numberBetween(100, 5000),
                'materialRemark'=> $faker->randomElement(['Premium Paper', 'Glossy', 'Matte', 'Recycled']),
                'productRemark' => $faker->sentence(8),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
