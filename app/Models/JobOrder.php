<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    protected $fillable = ['order_id', 'job_description', 'due_date', 'status'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}