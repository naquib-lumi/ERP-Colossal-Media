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