<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Material extends Model
{
    use HasFactory;
    protected $table = 'materials';
    protected $primaryKey = 'MaterialID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    // stock_quantity / stock_volume are left out on purpose: they change only
    // through App\Services\MaterialStockService, which logs every movement.
    protected $fillable = [
        'UserID', 'materialName', 'materialDescription', 'material_type_id', 'unitCost', 'pastUsageReference', 'active',
        'quantity_unit', 'low_stock_quantity', 'low_stock_volume',
    ];
    protected $casts = [
        'unitCost' => 'decimal:6',
        'active' => 'boolean',
        'stock_quantity' => 'decimal:2',
        'stock_volume' => 'decimal:2',
        'low_stock_quantity' => 'decimal:2',
        'low_stock_volume' => 'decimal:2',
    ];

    public function stockMovements()
    {
        return $this->hasMany(MaterialStockMovement::class, 'material_id', 'MaterialID');
    }

    /** At or below an alert level that has been set (volume in sq inch). */
    public function isLowStock(): bool
    {
        return ($this->low_stock_quantity !== null && (float) $this->stock_quantity <= (float) $this->low_stock_quantity)
            || ($this->low_stock_volume !== null && (float) $this->stock_volume <= (float) $this->low_stock_volume);
    }
    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }
    public function materialType()
    {
        return $this->belongsTo(MaterialType::class, 'material_type_id');
    }
    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'ItemID', 'ItemID');
    }
    public function primaryOfItems()
    {
        return $this->hasMany(ProductItem::class, 'MaterialID', 'MaterialID');
    }
}