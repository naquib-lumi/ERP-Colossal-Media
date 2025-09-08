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
    protected $casts = [
        'material' => 'array',
        'prime_centre'  => 'boolean',
    ];

    protected $fillable = [
        'ProductID','itemName','quantity',
        'sizeWidth','sizeHeight', 'sizeUnit',
        'bleedTop','bleedBottom','bleedLeft','bleedRight', 'bleedUnit',
        'finishing','renderTime','material', 'prime_centre',
    ];

    // ── Relations
    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }

    public function primaryMaterial()
    {
        return $this->belongsTo(Material::class, 'MaterialID', 'MaterialID');
    }

    public function materials()
    {
        return $this->belongsToMany(
            Material::class,
            'item_material', 
            'ItemID',
            'MaterialID'
        );
    }

    public function spec()
    {
        return $this->hasOne(Specification::class, 'ItemID', 'ItemID');
    }
}
