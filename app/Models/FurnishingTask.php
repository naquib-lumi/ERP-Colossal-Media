<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnishingTask extends Model
{
    protected $table = 'furnishing_tasks';
    protected $fillable = [
        'product_item_id',
        'assignee',
        'status',
        'cutter',
        'lamination',
        'machine',
        'need_hot_press',
        'need_assemble',
        'proof_required',
        'notes',
        'finished_at',
    ];

    protected $casts = [
        'need_hot_press' => 'bool',
        'need_assemble'  => 'bool',
        'proof_required' => 'bool',
        'finished_at'    => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FurnishingProductItem::class, 'product_item_id');
    }
}
