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
            ->where('role', 'salesperson'); // Restrict to salesperson role
    }

    public function attachments()
    {
        return $this->hasMany(LeadAttachment::class, 'lead_id');
    }
}