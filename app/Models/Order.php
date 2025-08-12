<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id','lead_id','leadName','leadPhone','companyName',
        'orderDate','deadline','leadEmail','orderTitle','orderDetail',
        'orderStatus','orderAttachment','approval','taskType','draft','pending'
    ];

    protected $casts = [
        'orderDate' => 'date',
        'deadline'  => 'date',
        'approval'  => 'boolean',
        'draft'     => 'boolean',
        'pending'   => 'boolean',
        'attachments' => 'array',
    ];

    // Relations
    // public function user() { 
    //     return $this->belongsTo(User::class, 'user_id')->withDefault([
    //         'name' => '—',
    //     ]);    
    // }

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
}
