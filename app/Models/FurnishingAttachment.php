<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FurnishingAttachment extends Model
{
    protected $table = 'furnishing_attachments';

    protected $fillable = [
        'product_item_id',
        'disk',
        'path',
        'original',
        'mime',
        'size',
    ];

    public function product(): BelongsTo
    {
        // Use positional args, not named args
        return $this->belongsTo(FurnishingProductItem::class, 'product_item_id');
    }

    // Classic accessor (works fine in Laravel 8/9/10)
    public function getUrlAttribute(): string
    {
        // Use positional arg and import Storage facade
        return Storage::disk($this->disk)->url($this->path);
    }
}
