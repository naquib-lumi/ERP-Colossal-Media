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

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}