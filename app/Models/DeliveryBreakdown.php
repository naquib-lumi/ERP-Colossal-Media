<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryBreakdown extends Model
{
    use HasFactory;

    protected $table = 'delivery_breakdowns';
    protected $primaryKey = 'BreakdownID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'ProductID', 'method', 'quantity', 'date', 'time', 'location',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s', // or 'string' if you prefer
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }
}
