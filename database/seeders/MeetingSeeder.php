<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use Carbon\Carbon;

class MeetingSeeder extends Seeder
{
    public function run()
    {
        $titles = [
            'Client Meeting - ABC Corp',
            'Product Demo - XYZ Ltd',
            'Follow-Up - DEF Inc',
            'Strategy Session - GHI Group',
            'Planning Meeting - JKL Co.',
            'Technical Review - MNO Ltd',
            'Sales Pitch - PQR Inc',
            'Onboarding - STU Solutions',
            'Check-in - VWX Group',
            'Budget Discussion - YZA Corp',
        ];

        $notes = [
            'Discuss project details and scope',
            'Demo new product line to client',
            'Follow up on proposal submission',
            'Review Q3 goals and objectives',
            'Plan next steps for project',
            'Review bug reports and fixes',
            'Discuss onboarding process',
            'Final review before product launch',
            'Status update with client team',
            'Budget allocation and cost discussion',
        ];

        $types = ['Zoom', 'In-person', 'Phone', 'Microsoft Teams'];
        $locations = [
            'https://zoom.us/u/1234567890',
            '123 Main St, City, Country',
            'Conference Room A, HQ',
            'https://teams.microsoft.com/l/meetup-join/123',
        ];
        $attendees = [
            'client1@example.com,client2@example.com',
            'john.doe@techsolutions.com',
            'sarah.johnson@creativeagency.com',
            'mike.davis@startupxyz.com',
        ];

        for ($i = 0; $i < 20; $i++) {
            $start = Carbon::now()->addDays(rand(0, 14))->setTime(rand(9, 16), 0);
            $end = (clone $start)->addMinutes(rand(30, 90));
            $newStart = rand(0, 3) === 0 ? (clone $start)->addDays(rand(1, 7)) : null;
            $newEnd = $newStart ? (clone $newStart)->addMinutes(rand(30, 90)) : null;

            Meeting::create([
                'lead_id' => rand(1, 5), // Assumes at least 5 leads exist
                'user_id' => rand(1, 2), // Assumes at least 2 users exist
                'title' => $titles[array_rand($titles)],
                'start_time' => $start,
                'end_time' => $end,
                'type' => $types[array_rand($types)],
                'location' => $locations[array_rand($locations)],
                'attendees_email' => $attendees[array_rand($attendees)],
                'note' => $notes[array_rand($notes)],
                'status' => ['scheduled', 'canceled', 'postponed'][array_rand(['scheduled', 'canceled', 'postponed'])],
                'new_start_time' => $newStart,
                'new_end_time' => $newEnd,
            ]);
        }
    }
}