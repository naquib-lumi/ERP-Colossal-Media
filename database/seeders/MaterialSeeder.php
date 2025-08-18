<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // clear only if you want a reset (optional)
        // Material::truncate();

        for ($i = 0; $i < 50; $i++) {
            Material::create([
                'UserID'              => 1, // or auth user if you prefer
                'materialName'        => ucfirst($faker->unique()->words(2, true)),  // e.g., "Premium Vinyl"
                'materialDescription' => $faker->sentence(8),
                'unitType'            => $faker->randomElement(['roll','sheet','sqft','meter','piece']),
                'unitCost'            => $faker->randomFloat(2, 1, 50),
                'pastUsageReference'  => $faker->optional()->sentence(6),
            ]);
        }
    }
}
