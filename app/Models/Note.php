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
    'user_id',
];

    protected $casts = [
        'tags' => 'array',
           'date'=> 'datetime'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}