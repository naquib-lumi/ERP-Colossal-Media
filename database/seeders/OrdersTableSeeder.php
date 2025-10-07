<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;
use Carbon\Carbon;

class OrdersTableSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Pools
        $allUserIds     = DB::table('users')->pluck('id')->toArray();
        $artistIds      = DB::table('users')->where('role', 'artist')->pluck('id')->toArray();
        $salespersonIds = DB::table('users')->where('role', 'salesperson')->pluck('id')->toArray();
        $leadIds        = DB::table('leads')->pluck('id')->toArray();

        if (empty($allUserIds) || empty($leadIds)) {
            $this->command->warn('⚠ No users or leads found. Orders seeder skipped.');
            return;
        }
        // Fallbacks
        if (empty($artistIds)) {
            $artistIds = $allUserIds;
        }
        if (empty($salespersonIds)) {
            $salespersonIds = $allUserIds;
        }

        $statuses = ['to_assign', 'assigned', 'in_progress', 'pending', 'completed', 'rejected'];

        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $orderDate = $faker->dateTimeBetween('-60 days', 'now');
            $deadline  = Carbon::instance($orderDate)->addDays(rand(3, 15));

            $rows[] = [
                // Identifiers / relations
                'order_number'    => 'ORD-' . $orderDate->format('Ymd') . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'redo'            => null,  // can be updated later to point to another order id
                'artist_id'       => $faker->randomElement($artistIds),
                'salesperson_id'  => $faker->randomElement($salespersonIds),
                'lead_id'         => $faker->randomElement($leadIds),
                'data_entry_id'   => $faker->optional()->randomElement($allUserIds),

                // Lead / company info
                'leadName'        => $faker->name(),
                'leadPhone'       => $faker->phoneNumber(),
                'leadEmail'       => $faker->safeEmail(),
                'companyName'     => $faker->company(),

                // Dates
                'orderDate'       => $orderDate->format('Y-m-d'),
                'deadline'        => $deadline->format('Y-m-d'),

                // Order content
                'orderTitle'      => $faker->sentence(4),
                'orderDetail'     => $faker->paragraph(3),
                'orderAttachment' => null,

                // Status flags (match your schema defaults)
                'orderStatus'     => $faker->randomElement($statuses),
                'approval'        => (int) $faker->boolean(30), // tinyint(1)
                'draft'           => (int) $faker->boolean(20),
                'submit'          => (int) $faker->boolean(70),
                'status'          => (int) $faker->boolean(90), // active/inactive flag
                'pending'         => (int) $faker->boolean(40),

                // Timestamps
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('orders')->insert($rows);

        $this->command->info('✅ Inserted ' . count($rows) . ' dummy orders.');
    }
}
