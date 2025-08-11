<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ReminderNotification;
use Illuminate\Support\Facades\Log;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'title',
        'due_date',
        'status',
        'recurrence_type',
        'recurrence_time',
        'end_date',
        'is_auto',
        'last_notify_time',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'end_date' => 'date',
        'is_auto' => 'boolean',
        'last_notify_time' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function notifyUser()
    {
        \Log::info("Starting notifyUser for Reminder ID {$this->id}");
        if (!$this->lead || !$this->lead->user) {
            \Log::warning("Reminder ID {$this->id} has no associated lead or user.");
            return;
        }

        $user = $this->lead->user;
        \Log::info("Notifying user ID {$user->id} ({$user->email})");

        try {
            $user->notify(new ReminderNotification($this, $user));
            \Log::info("Notification sent for Reminder ID {$this->id} to user {$user->id}");

            \App\Models\NotificationLog::create([
                'user_id' => $user->id,
                'notification_type' => 'reminder',
                'data' => json_encode([
                    'reminder_id' => $this->id,
                    'title' => $this->title,
                    'due_date' => $this->due_date->format('Y-m-d H:i'),
                    'lead_id' => $this->lead_id,
                    'status' => $this->status,
                ]),
                'sent_at' => now(),
            ]);
            \Log::info("NotificationLog created for Reminder ID {$this->id}");
            $this->update(['last_notify_time' => now()]);
        } catch (\Exception $e) {
            \Log::error("Failed to send notification for Reminder ID {$this->id}: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        }
    }
}