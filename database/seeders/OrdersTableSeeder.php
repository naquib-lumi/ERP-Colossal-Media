<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class OrdersTableSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // FK pools
        $allUserIds     = DB::table('users')->pluck('id')->toArray();
        $artistIds      = DB::table('users')->where('role', 'artist')->pluck('id')->toArray();
        $salespersonIds = DB::table('users')->where('role', 'salesperson')->pluck('id')->toArray();
        $leadIds        = DB::table('leads')->pluck('id')->toArray();

        if (empty($allUserIds) || empty($leadIds)) {
            $this->command->warn('⚠ No users or leads found. Orders seeder skipped.');
            return;
        }

        // Fallbacks if specific roles don’t exist yet
        if (empty($artistIds)) {
            $artistIds = $allUserIds;
        }
        if (empty($salespersonIds)) {
            $salespersonIds = $allUserIds;
        }

        $statuses  = ['to_assign', 'assigned', 'in_progress', 'pending', 'completed', 'rejected'];
        $taskTypes = ['courier', 'delivery', 'installation', 'self_pickup'];

        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $orderDate = $faker->dateTimeBetween('-60 days', 'now');
            $deadline  = Carbon::instance($orderDate)->addDays(rand(3, 15));

            $rows[] = [
                'artist_id'       => $faker->randomElement($artistIds),        // NEW
                'salesperson_id'  => $faker->randomElement($salespersonIds),   // RENAMED FROM user_id
                'lead_id'         => $faker->randomElement($leadIds),

                'leadName'        => $faker->name,
                'leadPhone'       => $faker->phoneNumber,
                'companyName'     => $faker->company,

                'orderDate'       => $orderDate->format('Y-m-d'),
                'deadline'        => $deadline->format('Y-m-d'),

                'leadEmail'       => $faker->safeEmail,
                'orderTitle'      => $faker->sentence(4),
                'orderDetail'     => $faker->paragraph(3),
                'orderStatus'     => $faker->randomElement($statuses),
                'orderAttachment' => null,
                'approval'        => (int) $faker->boolean(30),   // tinyint(1)
                'taskType'        => $faker->randomElement($taskTypes),
                'draft'           => (int) $faker->boolean(20),
                'pending'         => (int) $faker->boolean(50),

                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('orders')->insert($rows);

        $this->command->info('✅ Inserted '.count($rows).' dummy orders.');
    }
}