<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPerson extends Model
{
    protected $fillable = ['user_id', 'phone', 'department'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}