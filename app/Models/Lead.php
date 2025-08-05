<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['sales_person_id', 'name', 'email', 'phone', 'notes', 'status'];

 public function user()
{
    return $this->belongsTo(User::class, 'user_id')
        ->where('role', 'salesperson'); // Only link to salespeople
}
    
}