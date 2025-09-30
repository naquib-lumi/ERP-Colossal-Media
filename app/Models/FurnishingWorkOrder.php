<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FurnishingWorkOrder extends Model
{
    protected $table = 'work_orders'; // reuse existing table if you already have one
    protected $fillable = [
        'code',
        'title',
        'company_name',
        'assigned_by',
        'received_date',
        'deadline',
        'design_confirmed',
        'customer_name',
        'customer_phone',
        'customer_email',
    ];

    protected $casts = [
        'received_date'    => 'date',
        'deadline'         => 'date',
        'design_confirmed' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(FurnishingProductItem::class, 'work_order_id');
    }
}
