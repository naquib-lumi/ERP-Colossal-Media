<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRemark extends Model
{
    protected $table = 'product_remarks';
    protected $primaryKey = 'RemarkID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // you have created_at/updated_at

    protected $fillable = ['ProductID','operation','remark'];

    public function product()
    {
        // product_remarks.ProductID -> products.ProductID
        return $this->belongsTo(\App\Models\Product::class, 'ProductID', 'ProductID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
