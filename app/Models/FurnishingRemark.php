<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnishingRemark extends Model
{
    protected $table = 'furnishing_remarks';
    protected $fillable = [
        'product_item_id',
        'body',
        'author',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FurnishingProductItem::class, 'product_item_id');
    }
}
