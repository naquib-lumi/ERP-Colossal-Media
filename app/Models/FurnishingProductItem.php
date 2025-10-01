<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FurnishingProductItem extends Model
{
    protected $table = 'product_items';
    protected $fillable = [
        'work_order_id',
        'product_code',
        'name',
        'material',
        'size',
        'spec_json',
        'qty',
    ];

    protected $casts = [
        'spec_json' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(FurnishingWorkOrder::class, 'work_order_id');
    }

    public function furnishingTask(): HasOne
    {
        return $this->hasOne(FurnishingTask::class, 'product_item_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(FurnishingDelivery::class, 'product_item_id');
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(FurnishingRemark::class, 'product_item_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FurnishingAttachment::class, 'product_item_id');
    }
}
