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
        'status',
        'opportunity',
        'remark',
    ];

 public function user()
{
    return $this->belongsTo(User::class, 'salesperson_id')
        ->whereIn('role', ['salesperson', 'head-salesperson', 'boss']);
}


    public function attachments()
    {
        return $this->hasMany(LeadAttachment::class, 'lead_id');
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function orders()
{
    return $this->hasMany(Order::class);
}

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    public function meetings()
    {
        return $this->hasMany(Meeting::class)->orderBy('start_time', 'asc');
    }
}