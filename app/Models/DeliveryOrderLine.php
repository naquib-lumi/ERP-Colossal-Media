<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderLine extends Model
{
    protected $fillable = [
        'delivery_order_id', 'product_id', 'delivery_breakdown_id',
        'description', 'method', 'quantity', 'delivery_date', 'delivery_time',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
