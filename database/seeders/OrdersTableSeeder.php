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

        // Fetch existing IDs for FK safety
        $userIds = DB::table('users')->pluck('id')->toArray();
        $leadIds = DB::table('leads')->pluck('id')->toArray();

        if (empty($userIds) || empty($leadIds)) {
            $this->command->warn("⚠ No users or leads found. Seeder skipped.");
            return;
        }

        $statuses = ['to_assign', 'assigned', 'in_progress', 'pending', 'completed', 'rejected'];
        $taskTypes = ['courier', 'delivery', 'installation', 'self_pickup'];

        $data = [];

        for ($i = 1; $i <= 30; $i++) {
            $orderDate = $faker->dateTimeBetween('-60 days', 'now');
            $deadline = Carbon::instance($orderDate)->addDays(rand(3, 15));

            $data[] = [
                'user_id'         => $faker->randomElement($userIds),
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
                'approval'        => $faker->boolean(30), // 30% chance approved
                'taskType'        => $faker->randomElement($taskTypes),
                'draft'           => $faker->boolean(20), // 20% chance draft
                'pending'         => $faker->boolean(50), // 50% chance pending
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('orders')->insert($data);

        $this->command->info("✅ Inserted " . count($data) . " dummy orders.");
    }
}
