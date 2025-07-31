<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['sales_person_id', 'customer_name', 'amount', 'order_date', 'status'];

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }
}