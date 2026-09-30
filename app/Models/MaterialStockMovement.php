<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

/** Append-only: created by App\Services\MaterialStockService, never edited. */
class MaterialStockMovement extends Model
{
    protected $table = 'material_stock_movements';

    public const UPDATED_AT = null;

    protected $fillable = [
        'material_id', 'material_name', 'type',
        'quantity_change', 'volume_change', 'quantity_after', 'volume_after',
        'reason', 'order_id', 'product_item_id', 'user_id',
    ];

    protected $casts = [
        'type'            => StockMovementType::class,
        'quantity_change' => 'decimal:2',
        'volume_change'   => 'decimal:2',
        'quantity_after'  => 'decimal:2',
        'volume_after'    => 'decimal:2',
        'created_at'      => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'MaterialID');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id', 'ItemID');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
