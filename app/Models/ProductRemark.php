<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRemark extends Model
{
    protected $table = 'product_remarks';
    protected $primaryKey = 'RemarkID';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['ProductID','operation','remark'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }
}