<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'content',
        'date',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}