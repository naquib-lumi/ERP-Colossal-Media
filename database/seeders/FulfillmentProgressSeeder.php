<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FulfillmentProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear table if you want a clean start (optional)
        // DB::table('fulfillment_progress')->truncate();

        $stages  = ['printing', 'furnishing', 'delivery', 'installation'];
        $now     = Carbon::now();

        // Seed for product IDs 1..20 (adjust as needed)
        for ($productId = 1; $productId <= 20; $productId++) {

            // Simulate a timeline per stage
            $accepted = (clone $now)->subDays(random_int(5, 25));

            foreach ($stages as $i => $stage) {
                // Randomize outcome (bias towards completed)
                $isCompleted = random_int(1, 100) <= 80; // 80% completed, 20% rejected
                $status      = $isCompleted ? 'completed' : 'rejected';

                // acceptedAt grows forward in time per stage
                $acceptedAt  = (clone $accepted)->addDays($i * 2 + random_int(0, 1));

                // If completed, set completedAt after acceptedAt; otherwise null
                $completedAt = $isCompleted
                    ? (clone $acceptedAt)->addHours(random_int(4, 48))
                    : null;

                DB::table('fulfillment_progress')->insert([
                    'ProductID'   => $productId,
                    'stage'       => $stage,                 // printing, furnishing, delivery, installation
                    'acceptedAt'  => $acceptedAt,            // timestamp
                    'completedAt' => $completedAt,           // timestamp|null
                    'status'      => $status,                // completed | rejected
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
