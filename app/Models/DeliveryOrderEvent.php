<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of a delivery order's history. Append-only. */
class DeliveryOrderEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['delivery_order_id', 'event', 'user_id', 'note'];

    protected $casts = ['created_at' => 'datetime'];

    public const LABELS = [
        'created'   => 'Created',
        'updated'   => 'Updated from the order',
        'cancelled' => 'Cancelled (location removed from the order)',
        'emailed'   => 'Emailed to client',
        'delivered' => 'Marked delivered',
    ];

    public function label(): string
    {
        return self::LABELS[$this->event] ?? ucfirst($this->event);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
