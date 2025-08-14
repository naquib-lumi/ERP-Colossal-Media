<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductItem extends Model
{
    use HasFactory;

    protected $table = 'product_items';
    protected $primaryKey = 'ItemID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'ProductID', 'MaterialID', 'itemName', 'quantity',
        'sizeWidth', 'sizeHeight', 'sizeLength',
        'bleedWidth', 'bleedHeight', 'bleedLength',
        'finishing', 'renderTime',
    ];

    // ── Relations
    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }

    // If you use a single “primary” material on the item (via product_items.MaterialID)
    public function primaryMaterial()
    {
        return $this->belongsTo(Material::class, 'MaterialID', 'MaterialID');
    }

    // If you attach many materials to an item (via materials.ItemID)
    public function materials()
    {
        return $this->hasMany(Material::class, 'ItemID', 'ItemID');
    }

    public function specification()
    {
        return $this->hasOne(Specification::class, 'ItemID', 'ItemID');
    }
}
