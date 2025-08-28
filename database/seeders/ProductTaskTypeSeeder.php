<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $taskTypes = ['printing', 'furnishing', 'installation', 'delivery'];

        // Update every product with a random taskType
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            DB::table('products')
                ->where('ProductID', $product->ProductID)
                ->update([
                    'taskType' => $taskTypes[array_rand($taskTypes)],
                ]);
        }
    }
}
