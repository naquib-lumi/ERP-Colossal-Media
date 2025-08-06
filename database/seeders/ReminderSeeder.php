<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reminder;
use Carbon\Carbon;

class ReminderSeeder extends Seeder
{
    public function run()
    {
        Reminder::create([
            'lead_id' => 1, // Assume lead ID 1 exists
            'title' => 'Follow up with John about the proposal.',
            'due_date' => Carbon::now()->subDays(1),
            'status' => 'overdue',
        ]);

        Reminder::create([
            'lead_id' => 1,
            'title' => 'Send contract for signature.',
            'due_date' => Carbon::now()->addDays(2),
            'status' => 'upcoming',
        ]);

        // Add more for other leads
    }
}