<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Note;
use Carbon\Carbon;

class NoteSeeder extends Seeder
{
    public function run()
    {
        Note::create([
            'lead_id' => 1, // Assume lead ID 1 exists
            'content' => 'Thursday, July 10, 2023 3:05 PM',
            'date' => Carbon::now(),
            'tags' => json_encode(['tag1', 'tag2']),
        ]);

        // Add more notes
    }
}