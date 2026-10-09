<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A product on a quotation, with materials picked from the material list. */
class QuotationProduct extends Model
{
    protected $fillable = ['quotation_id', 'sort', 'product_name', 'description', 'materials'];

    protected $casts = [
        'materials' => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort')->orderBy('id');
    }
}
