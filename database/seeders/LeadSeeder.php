<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\LeadAttachment;
use Carbon\Carbon;

class LeadSeeder extends Seeder
{
    public function run()
    {
        $statuses = ['accept', 'reject', 'followup', 'new'];
        $companyNames = ['Tech Solutions Inc.', 'Creative Agency Ltd.', 'StartupXYZ'];
        $websites = ['https://techsolutions.com', 'https://creativeagency.com', 'https://startupxyz.com', null];
        $names = ['Alice Smith', 'Bob Johnson', 'Charlie Brown', 'Sam Smith'];
        $emails = ['alice.smith@example.com', 'bob.johnson@example.com', 'charlie.brown@example.com', 'sam.smith@example.com'];
        $phones = ['123-456-7890', '098-765-4321', '555-123-4567', '666-789-0123'];
        $companyPhones = ['555-123-4567', '555-987-6543', '555-789-0123', null];
        $opportunities = ['High', 'Medium', 'Low', null];

        for ($month = 1; $month <= 8; $month++) {
            foreach ($statuses as $index => $status) {
                $salespersonId = rand(1, 2); // Randomly assign to salesperson ID 1 or 2
                $lead = Lead::create([
                    'salesperson_id' => $salespersonId,
                    'company_name' => $companyNames[array_rand($companyNames)],
                    'company_phone' => $companyPhones[array_rand($companyPhones)],
                    'website' => $websites[array_rand($websites)],
                    'name' => $names[$index % count($names)],
                    'phone' => $phones[$index % count($phones)],
                    'email' => $emails[$index % count($emails)],
                    'date' => Carbon::create(2025, $month, rand(1, 28)),
                    'status' => $status,
                    'opportunity' => $opportunities[array_rand($opportunities)],
                    'remark' => $status === 'new' ? '' : "Follow-up needed for $status in $month",
                    'created_at' => Carbon::create(2025, $month, rand(1, 28), rand(0, 23), rand(0, 59), rand(0, 59)),
                    'updated_at' => Carbon::now(),
                ]);

                // Create a random attachment for each lead
                if (rand(0, 1)) { // 50% chance of having an attachment
                    LeadAttachment::create([
                        'lead_id' => $lead->id,
                        'user_id' => $salespersonId,
                        'file_size' => rand(1000, 10000000), // Random size in bytes (1KB to 10MB)
                        'file_location' => 'storage/leads/' . $lead->id . '/document_' . rand(1, 10) . '.pdf',
                        'file_extension' => 'pdf', // Assuming PDF as per PAGE19
                    ]);
                }
            }
        }
    }
}