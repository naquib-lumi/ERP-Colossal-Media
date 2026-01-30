<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRecord extends Model
{
    protected $fillable = [
        'order_id',
        'first_created_at',
        'first_edited_at',
        'submitted_at',
    ];
}