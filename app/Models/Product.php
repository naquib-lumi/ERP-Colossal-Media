<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ProductID';    // <-- matches your migration/ERD
    public $incrementing = true;
    public $timestamps = true;

    // allow both camelCase & snake_case columns (in case your table differs)
    protected $fillable = [
        'OrderID',
        'productName', 'product_name',
        'totalQuantity', 'total_quantity',
        'materialRemark', 'material_remark',
        'productRemark', 'product_remark',
    ];
}
