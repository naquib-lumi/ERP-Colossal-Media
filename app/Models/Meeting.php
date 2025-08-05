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
        'attendees_email',
        'note',
        'status',
        'new_start_time',
        'new_end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'new_start_time' => 'datetime',
        'new_end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->where('role', 'salesperson'); // Restrict to salesperson role
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}