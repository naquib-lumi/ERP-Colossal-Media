<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FulfillmentProgress extends Model
{
    use HasFactory;

    protected $table = 'fulfillment_progress';
    protected $primaryKey = 'ProgressID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'ProductID', 'stage', 'acceptedAt', 'completedAt', 'status',
    ];

    protected $casts = [
        'acceptedAt' => 'datetime',
        'completedAt' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }
}
