<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;

class LeadSeeder extends Seeder
{
    public function run()
    {
        // Use the existing salesperson (id = 1)
        $salesPersonId = 1;

        // Dummy leads for Sales Person A1
        Lead::create([
            'user_id' => $salesPersonId,
            'name' => 'Alice Smith',
            'email' => 'alice.smith@example.com',
            'phone' => '123-456-7890',
            'notes' => 'Initial contact made.',
            'status' => 'accept',
        ]);

        Lead::create([
            'user_id' => $salesPersonId,
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
            'phone' => '098-765-4321',
            'notes' => 'Needs review.',
            'status' => 'reject',
        ]);

        Lead::create([
            'user_id' => $salesPersonId,
            'name' => 'Charlie Brown',
            'email' => 'charlie.brown@example.com',
            'phone' => '555-123-4567',
            'notes' => 'Follow up next week.',
            'status' => 'followup',
        ]);

         Lead::create([
            'user_id' => $salesPersonId,
            'name' => 'Sam Smith',
            'email' => 'Sam.Smith@example.com',
            'phone' => '555-123-4567',
            'notes' => '',
            'status' => 'new',
        ]);
    }
}