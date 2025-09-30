<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnishingDelivery extends Model
{
    protected $table = 'furnishing_deliveries';
    protected $fillable = [
        'product_item_id',
        'method',
        'address',
        'scheduled_at',
        'quantity',
        'contact_name',
        'contact_phone',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FurnishingProductItem::class, 'product_item_id');
    }
}
