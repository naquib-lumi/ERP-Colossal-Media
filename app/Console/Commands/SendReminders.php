<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send due one-time reminders';

    public function handle()
    {
        try {
            $now = Carbon::now('Asia/Kuala_Lumpur');
            $this->info("Current time: " . $now->toDateTimeString());

   $reminders = Reminder::whereIn('status', ['upcoming', 'overdue'])
    ->where('remind_at', '<=', $now)
    ->whereNull('last_notify_time')
    ->with('creator')
    ->get();

            $this->info("Found " . $reminders->count() . " due reminders.");

            foreach ($reminders as $reminder) {
                $this->info("Processing Reminder ID: {$reminder->id}");

                if (!$reminder->creator) {
                    $this->warn("Reminder ID {$reminder->id} has no creator.");
                    Log::warning("Reminder ID {$reminder->id} has no creator.");
                    continue;
                }

                //Helpers::notify($reminder->creator, $reminder->title, url('/leads/' . $reminder->lead_id.'/view'), ['database', 'mail']);
                Helpers::notifyReminder($reminder->creator, $reminder, ['database', 'mail']);
                
                $reminder->update(['last_notify_time' => $now]);
                $this->info("Notification sent for Reminder ID: {$reminder->id}");

                $this->info("==========================================");
            }

            $this->info('Processed ' . $reminders->count() . ' reminders successfully.');
        } catch (\Exception $e) {
            Log::error('Error processing reminders: ' . $e->getMessage());
            $this->error('Failed to process reminders. Check logs for details.');
        }
    }
}