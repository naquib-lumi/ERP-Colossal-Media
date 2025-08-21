<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAttachment extends Model
{
    protected $table = 'order_attachments';   // change if different
    protected $primaryKey = 'id';             // e.g. AttachmentID if that’s your PK
    // public $incrementing = true;
    // protected $keyType = 'int';

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');  // adjust keys if needed
    }
}
