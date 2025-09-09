<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Helpers\Helpers;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send due reminders and handle recurrences';

    public function handle()
    {
        try {
            // $now = Carbon::parse('2025-08-11 15:30:00', 'Asia/Kuala_Lumpur');  
            $now = Carbon::now('Asia/Kuala_Lumpur'); // Explicitly set timezone
            $this->info("Current time: " . $now->toDateTimeString());

            $reminders = Reminder::where('status', 'upcoming')
                ->with('lead.user')
                ->get();

            $this->info("Found " . $reminders->count() . " upcoming reminders.");

            foreach ($reminders as $reminder) {
                $this->info("Processing Reminder ID: {$reminder->id}");

                if (!$reminder->lead || !$reminder->lead->user) {
                    $this->warn("Reminder ID {$reminder->id} has no associated lead or user.");
                    Log::warning("Reminder ID {$reminder->id} has no associated lead or user.");
                    continue;
                }

                $lastNotify = $reminder->last_notify_time ?? Carbon::now('Asia/Kuala_Lumpur')->subDays(100);

                // Skip if notified within last 5 minutes
                if ($lastNotify >= $now->subMinutes(5)) {
                    $this->info("Already notified within 5 minutes for Reminder ID {$reminder->id}");
                    continue;
                }

                $shouldNotify = false;

                if ($reminder->recurrence_type === 'none') {
                    $this->info("Checking non-recurring reminder ID {$reminder->id}");
                    $notifyTime = $reminder->due_date->copy()->addMinutes($reminder->custom_offset ?? ($reminder->is_auto ? -60 : 0));
                    $shouldNotify = $notifyTime->between($now->copy()->subHour(), $now->copy()->addHour()) ||
                                   ($notifyTime->lte($now) && $notifyTime->gte($now->copy()->subDay()));
                    $this->info("Non-recurring shouldNotify: " . ($shouldNotify ? 'true' : 'false') . 
                                ", Notify Time: {$notifyTime}, Due Date: {$reminder->due_date}, Now: {$now}, 1 Hour Before: " . $now->copy()->subHour() . ", 1 Hour After: " . $now->copy()->addHour());
                } else {
                    $this->info("Checking recurring reminder ID {$reminder->id}, Recurrence Type: {$reminder->recurrence_type}");
                    $currentCycle = $reminder->due_date->copy()->startOfDay();
                    $currentTime = $now->copy()->setTimeFromTimeString($reminder->recurrence_time ?? $now->format('H:i'));

                    while ($currentCycle->lte($now)) {
                        switch ($reminder->recurrence_type) {
                            case 'daily':
                                $currentCycle->addDay();
                                break;
                            case 'weekly':
                                $currentCycle->addWeek();
                                break;
                            case 'monthly':
                                $currentCycle->addMonth();
                                break;
                        }
                    }

                    $nextNotify = $currentCycle->setTimeFromTimeString($reminder->recurrence_time ?? $now->format('H:i'));
                    $notifyTime = $nextNotify->copy()->addMinutes($reminder->custom_offset ?? ($reminder->is_auto ? -60 : 0));
                    $shouldNotify = $notifyTime->between($now->copy()->subHour(), $now->copy()->addHour()) && $lastNotify->lt($notifyTime);
                    $this->info("Recurring shouldNotify: " . ($shouldNotify ? 'true' : 'false') . 
                                ", Next Notify: {$nextNotify}, Notify Time: {$notifyTime}, Last Notify: {$lastNotify}");
                }

                $this->info("Final shouldNotify for Reminder ID {$reminder->id}: " . ($shouldNotify ? 'true' : 'false'));
                if ($shouldNotify) {
                    $this->info("Sending notification for Reminder ID {$reminder->id}");
                    try {
                        Helpers::notify($reminder->lead->user, "Reminder: {$reminder->title}", url('/leads/' . $reminder->lead_id), ['database', 'mail']);
                        $this->info("Notification sent successfully for Reminder ID {$reminder->id}");
                    } catch (\Exception $e) {
                        $this->error("Failed to send notification for Reminder ID {$reminder->id}: " . $e->getMessage());
                        Log::error("Failed to send notification for Reminder ID {$reminder->id}: " . $e->getMessage());
                    }

                    $reminder->update(['last_notify_time' => $now]);
                    $this->info("Updated last_notify_time to: " . $now->toDateTimeString());

                    if ($reminder->due_date->lte($now)) {
                        $reminder->update(['status' => 'overdue']);
                        $this->info("Updated status to 'overdue' for Reminder ID {$reminder->id}");
                    }
                } else {
                    $this->info("No notification needed for Reminder ID {$reminder->id}");
                }

                if ($reminder->is_auto && $reminder->due_date->gt($now) && !$this->hasAutoReminder($reminder)) {
                    $this->createAutoReminder($reminder);
                }

                if ($shouldNotify && $reminder->recurrence_type !== 'none' && $reminder->due_date->lte($now)) {
                    $nextDue = $reminder->due_date->copy();
                    switch ($reminder->recurrence_type) {
                        case 'daily':
                            $nextDue->addDay();
                            break;
                        case 'weekly':
                            $nextDue->addWeek();
                            break;
                        case 'monthly':
                            $nextDue->addMonth();
                            break;
                    }

                    if ($reminder->recurrence_time) {
                        [$hour, $minute] = explode(':', $reminder->recurrence_time);
                        $nextDue->setTime($hour, $minute, 0);
                    }

                    if ($nextDue->lte($reminder->end_date)) {
                        $newReminder = Reminder::create([
                            'lead_id' => $reminder->lead_id,
                            'title' => $reminder->title,
                            'due_date' => $nextDue,
                            'status' => 'upcoming',
                            'recurrence_type' => $reminder->recurrence_type,
                            'recurrence_time' => $reminder->recurrence_time,
                            'end_date' => $reminder->end_date,
                            'is_auto' => $reminder->is_auto,
                            'last_notify_time' => null,
                        ]);
                        $this->info("Created new reminder ID: {$newReminder->id} for next due date: {$nextDue}");
                    }
                }

                $this->info("==========================================");
            }

            $this->info('Processed ' . $reminders->count() . ' reminders successfully.');
        } catch (\Exception $e) {
            Log::error('Error processing reminders: ' . $e->getMessage());
            $this->error('Failed to process reminders. Check logs for details.');
        }
    }

    private function hasAutoReminder(Reminder $reminder)
    {
        return Reminder::where('lead_id', $reminder->lead_id)
            ->where('due_date', $reminder->due_date->copy()->subHour())
            ->where('is_auto', true)
            ->exists();
    }

    private function createAutoReminder(Reminder $reminder)
    {
        $autoDue = $reminder->due_date->copy()->subHour();
        $autoReminder = Reminder::create([
            'lead_id' => $reminder->lead_id,
            'title' => 'Auto 1-Hour Before: ' . $reminder->title,
            'due_date' => $autoDue,
            'status' => 'upcoming',
            'recurrence_type' => 'none',
            'is_auto' => true,
            'last_notify_time' => null,
        ]);
        $this->info("Created auto 1-hour-before reminder ID: {$autoReminder->id} for due date: {$autoDue}");
    }
}