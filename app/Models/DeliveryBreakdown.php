<?php

namespace App\Models;
use Carbon\Carbon;

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
    protected $guarded = [];  

    protected $fillable = [
        'ProductID', 'method', 'quantity', 'date', 'time', 'location', 'deliver_install_type', 'outsource_cost',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s', 
        'outsource_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }

    public function getWhenAttribute(): Carbon
    {
        // base date
        $date = Carbon::parse($this->date);

        $time = $this->time;
        if (empty($time)) {
            return $date; // no time stored
        }

        // if time has a date part, normalize to "H:i:s"
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            try {
                $time = Carbon::parse($time)->format('H:i:s');
            } catch (\Throwable $e) {
                return $date; // fallback if parse fails
            }
        }

        return $date->copy()->setTimeFromTimeString($time);
    }

    public function getRouteKeyName()
    {
        return 'BreakdownID';
    }
}
