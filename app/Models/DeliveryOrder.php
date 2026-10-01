<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    public const STATUS_ISSUED    = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id', 'location_key', 'location', 'methods', 'delivery_date', 'delivery_time',
        'status', 'content_hash', 'emailed_at', 'emailed_to', 'emailed_hash', 'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'emailed_at'    => 'datetime',
    ];

    protected static function booted()
    {
        // Number from the id, like orders: DO-2026-0001. Unique without a separate counter.
        static::created(function (DeliveryOrder $do) {
            $do->do_number = 'DO-' . now()->format('Y') . '-' . str_pad((string) $do->id, 4, '0', STR_PAD_LEFT);
            $do->saveQuietly();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function lines()
    {
        return $this->hasMany(DeliveryOrderLine::class)->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** Emailed, and the lines changed afterwards. */
    public function changedSinceEmailed(): bool
    {
        return $this->emailed_hash !== null && $this->emailed_hash !== $this->content_hash;
    }

    public function totalQuantity(): int
    {
        return (int) $this->lines->sum('quantity');
    }
}
