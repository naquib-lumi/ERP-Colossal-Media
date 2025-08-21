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

    protected $fillable = ['UserID','materialName','materialDescription','unitType','unitCost','pastUsageReference'];


    // ── Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }

    // The item that this material is attached to (via materials.ItemID)
    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'ItemID', 'ItemID');
    }

    // If an item selected this material as its “primary” (via product_items.MaterialID)
    public function primaryOfItems()
    {
        return $this->hasMany(ProductItem::class, 'MaterialID', 'MaterialID');
    }
}
