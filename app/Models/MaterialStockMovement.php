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
        'quantity_change', 'quantity_after',
        'reason', 'user_id',
    ];

    protected $casts = [
        'type'            => StockMovementType::class,
        'quantity_change' => 'integer',
        'quantity_after'  => 'integer',
        'created_at'      => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'MaterialID');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
