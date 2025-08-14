<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\LeadAttachment;
use App\Models\User;
use Carbon\Carbon;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        // Get actual salespeople user IDs
        $salespeople = User::where('role', 'salesperson')->pluck('id')->all();

        // If none exist, create a couple of salespeople
        if (count($salespeople) < 2) {
            $u1 = User::factory()->create(['name' => 'Sales One', 'email' => 'sales1@example.com', 'role' => 'salesperson']);
            $u2 = User::factory()->create(['name' => 'Sales Two', 'email' => 'sales2@example.com', 'role' => 'salesperson']);
            $salespeople = [$u1->id, $u2->id];
        }

        $statuses       = ['accept', 'reject', 'followup', 'new'];   // make sure these match your leads.status enum
        $companyNames   = ['Tech Solutions Inc.', 'Creative Agency Ltd.', 'StartupXYZ'];
        $websites       = ['https://techsolutions.com', 'https://creativeagency.com', 'https://startupxyz.com', null];
        $names          = ['Alice Smith', 'Bob Johnson', 'Charlie Brown', 'Sam Smith'];
        $emails         = ['alice.smith@example.com', 'bob.johnson@example.com', 'charlie.brown@example.com', 'sam.smith@example.com'];
        $phones         = ['123-456-7890', '098-765-4321', '555-123-4567', '666-789-0123'];
        $companyPhones  = ['555-123-4567', '555-987-6543', '555-789-0123', null];
        $opportunities  = ['High', 'Medium', 'Low', null];

        for ($month = 1; $month <= 8; $month++) {
            foreach ($statuses as $index => $status) {
                $salespersonId = $salespeople[array_rand($salespeople)];

                $lead = Lead::create([
                    'salesperson_id' => $salespersonId,
                    'company_name'   => $companyNames[array_rand($companyNames)],
                    'company_phone'  => $companyPhones[array_rand($companyPhones)],
                    'website'        => $websites[array_rand($websites)],
                    'name'           => $names[$index % count($names)],
                    'phone'          => $phones[$index % count($phones)],
                    'email'          => $emails[$index % count($emails)],
                    'date'           => Carbon::create(2025, $month, rand(1, 28)),
                    'status'         => $status,  // must be valid per your enum
                    'opportunity'    => $opportunities[array_rand($opportunities)],
                    'remark'         => $status === 'new' ? '' : "Follow-up needed for $status in $month",
                    'created_at'     => Carbon::create(2025, $month, rand(1, 28), rand(0, 23), rand(0, 59), rand(0, 59)),
                    'updated_at'     => Carbon::now(),
                ]);

                // 50% chance to attach a file
                if (rand(0, 1)) {
                    LeadAttachment::create([
                        'lead_id'        => $lead->id,
                        'user_id'        => $salespersonId, // also FK to users.id
                        'file_size'      => rand(1_000, 10_000_000),
                        'file_location'  => 'storage/leads/'.$lead->id.'/document_'.rand(1, 10).'.pdf',
                        'file_extension' => 'pdf',
                    ]);
                }
            }
        }
    }
}
