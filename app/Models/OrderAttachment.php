<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OrderAttachment extends Model
{
    protected $table = 'order_attachments';

    protected $fillable = [
        'order_id', 'user_id', 'file_path', 'original_name', 'mime_type', 'size'
    ];

    public function order()    { return $this->belongsTo(Order::class); }
    public function uploader()  { return $this->belongsTo(User::class, 'user_id'); }

    public function url()
    {
        return Storage::url($this->file_path);
    }
}