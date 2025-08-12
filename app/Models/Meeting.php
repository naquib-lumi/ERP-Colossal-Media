<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'title',
        'start_time',
        'end_time',
        'type',
        'location',
        'url', // Add url to fillable
        'note',
        'status',
        'new_start_time',
        'new_end_time',
        'duration',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'new_start_time' => 'datetime',
        'new_end_time' => 'datetime',
        'duration' => 'integer',
    ];

    protected static function booted()
    {
        static::created(function ($meeting) {
            $reminderTime = $meeting->start_time->subMinutes(15);
            $reminder = $meeting->lead->reminders()->create([
                'title' => 'Meeting Reminder: ' . $meeting->title,
                'due_date' => $reminderTime,
                'status' => 'upcoming',
                'is_auto' => true,
            ]);

            $reminder->notifyUser();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->where('role', 'salesperson');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}