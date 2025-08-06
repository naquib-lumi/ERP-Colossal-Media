<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'title',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime', // Ensure due_date is cast as a datetime
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}