<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPermit extends Model
{
    protected $table = 'product_permit';
    public $timestamps = false;

    protected $fillable = [
        'user_id','product_id','permit_file','uploaded_at','created_at','updated_at'
    ];

    protected $dates = ['uploaded_at','created_at','updated_at'];

    public function user(){ return $this->belongsTo(User::class); }
    public function product(){ return $this->belongsTo(Product::class, 'product_id', 'ProductID'); }
}
