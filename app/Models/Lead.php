<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'salesperson_id',
        'company_name',
        'company_phone',
        'website',
        'name',
        'phone',
        'email',
        'date',
        'status',
        'opportunity',
        'remark',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'salesperson_id')
            ->where('role', 'salesperson');
    }

    public function attachments()
    {
        return $this->hasMany(LeadAttachment::class, 'lead_id');
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}