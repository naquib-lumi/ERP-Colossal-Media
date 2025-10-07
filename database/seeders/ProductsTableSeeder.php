<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Detect the primary key column on orders: 'OrderID' (camel) or fallback to 'id'
        $orderPk = Schema::hasColumn('orders', 'OrderID')
            ? 'OrderID'
            : (Schema::hasColumn('orders', 'id') ? 'id' : null);

        if (!$orderPk) {
            throw new \RuntimeException(
                "Could not find an 'OrderID' or 'id' column on the 'orders' table."
            );
        }

        $orderIds = DB::table('orders')->pluck($orderPk)->toArray();

        if (empty($orderIds)) {
            throw new \RuntimeException(
                "No orders found. Seed or create orders first so products can reference a valid OrderID."
            );
        }

        for ($i = 0; $i < 30; $i++) {
            DB::table('products')->insert([
                'OrderID'        => $faker->randomElement($orderIds),               // NOT NULL
                'redoOf'         => $faker->optional()->numberBetween(1, 20),       // NULL allowed
                'productName'    => ucfirst($faker->word()) . ' ' . ucfirst($faker->word()),
                'totalQuantity'  => $faker->numberBetween(50, 5000),
                'productRemark'  => $faker->optional()->sentence(8),                // NULL allowed
                'materialRemark' => $faker->optional()->randomElement([
                    'Glossy','Matte','Premium Paper','Recycled','Outdoor Vinyl'
                ]),                                                                 // NULL allowed (TEXT)
                'taskType'       => $faker->optional()->randomElement([
                    'Printing','Cutting','Mounting','Furnishing','Packaging'
                ]),                                                                 // NULL allowed
                // 'status' and 'editable' have defaults in your schema (in_progress, 1)
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
