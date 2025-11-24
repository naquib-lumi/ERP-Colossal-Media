<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id', 'order_number', 'artist_id', 'lead_id', 'leadName', 'leadPhone', 'companyName', 'salesperson_id',
        'orderDate', 'deadline', 'leadEmail', 'orderTitle', 'orderDetail',
        'orderStatus', 'approval', 'taskType', 'draft', 'pending', 'submit', 'status'
    ];

    protected $casts = [
        'orderDate' => 'date',
        'deadline'  => 'date',
        'approval'  => 'boolean',
        'draft'     => 'boolean',
        'pending'   => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function ($order) {
            $order->order_number = '#ORD-' . $order->orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $order->save();
        });
    }

    // Relations
    public function artist()         { return $this->belongsTo(User::class, 'artist_id'); }
    public function salesperson()     { return $this->belongsTo(User::class, 'salesperson_id'); }
    public function lead()           { return $this->belongsTo(Lead::class); }
    public function products()        { return $this->hasMany(Product::class, 'OrderID'); }

    // NEW: Only this one
    public function attachments()
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $map = [
            'to_assign'   => 'Assign', 'assigned' => 'Assigned', 'in_progress' => 'In Progress',
            'pending'     => 'Pending', 'completed' => 'Completed', 'rejected' => 'Rejected',
        ];
        return $map[$this->orderStatus] ?? ucfirst(str_replace('_', ' ', $this->orderStatus));
    }
        public function deliveryBreakdowns()
    {
        return $this->hasManyThrough(
            DeliveryBreakdown::class, // final model
            Product::class,           // through model
            'OrderID',                // FK on products that points to orders.id
            'ProductID',              // FK on delivery_breakdowns that points to products.ProductID
            'id',                     // local key on orders
            'ProductID'               // local key on products for the hasMany side
        );
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->pending ? 'pending' : $this->orderStatus;
    }

    // Optional: keep old accessor only if any legacy code uses it
    public function getAttachmentPathsAttribute(): Collection
    {
        return $this->attachments->pluck('file_path')->filter();
    }
        public function redoReports()
    {
        return $this->hasMany(\App\Models\ReportRedo::class, 'OrderID', 'id');
    }

    public function originalOrder()
    {
        return $this->belongsTo(self::class, 'redo', 'id');
    }

    public function dataEntry()
    {
        return $this->belongsTo(User::class, 'data_entry_id');
    }
}