<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['sales_person_id', 'title', 'start_time', 'end_time', 'description', 'status'];

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class);
    }
}