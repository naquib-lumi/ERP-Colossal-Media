<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id','lead_id','leadName','leadPhone','companyName','salesperson_id',
        'orderDate','deadline','leadEmail','orderTitle','orderDetail',
        'orderStatus','orderAttachment','approval','taskType','draft','pending', 'submit'
    ];

    protected $casts = [
        'orderDate' => 'date',
        'deadline'  => 'date',
        'approval'  => 'boolean',
        'draft'     => 'boolean',
        'pending'   => 'boolean',
        'attachments' => 'array',
        //test
    ];


    protected static function booted()
{
    static::created(function ($order) {
        $order->order_number = '#ORD-' . $order->orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $order->save();
    });
}

    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }


    public function lead() { 
        return $this->belongsTo(Lead::class); 
    }

    public function leadAttachments()
    {
        return $this->hasMany(\App\Models\LeadAttachment::class, 'lead_id', 'lead_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        $map = [
            'to_assign'   => 'Assign',
            'assigned'    => 'Assigned',
            'in_progress' => 'In Progress',
            'pending'     => 'Pending',
            'completed'   => 'Completed',
            'rejected'    => 'Rejected',
        ];
        return $map[$this->orderStatus] ?? ucfirst($this->orderStatus);
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'OrderID', 'id');
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
        return $this->pending ? 'pending' : (string) $this->orderStatus;
    }

    public function getAttachmentPathsAttribute(): Collection
    {
        return collect(explode(',', (string) $this->orderAttachment))
            ->map(fn ($p) => trim($p))
            ->filter(); // remove empties
    }
}
