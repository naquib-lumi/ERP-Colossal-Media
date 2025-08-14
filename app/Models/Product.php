<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ProductID';    
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    // allow both camelCase & snake_case columns (in case your table differs)
    protected $fillable = [
        'OrderID',
        'productName', 'product_name',
        'totalQuantity', 'total_quantity',
        'materialRemark', 'material_remark',
        'productRemark', 'product_remark',
    ];

    // ── Relations
    public function order()
    {
        return $this->belongsTo(Order::class, 'OrderID', 'id');
    }

    public function items()
    {
        return $this->hasMany(ProductItem::class, 'ProductID', 'ProductID');
    }

    public function breakdowns()
    {
        return $this->hasMany(DeliveryBreakdown::class, 'ProductID', 'ProductID');
    }

    public function progress()
    {
        return $this->hasMany(FulfillmentProgress::class, 'ProductID', 'ProductID');
    }
}
