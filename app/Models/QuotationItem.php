<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One priced line of a quotation product: size, quantity, unit price and total, all typed by hand. */
class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_product_id', 'sort', 'description',
        'size_width', 'size_height', 'size_unit',
        'quantity', 'quantity_unit', 'unit_price', 'total',
    ];

    protected $casts = [
        'size_width'  => 'decimal:2',
        'size_height' => 'decimal:2',
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(QuotationProduct::class, 'quotation_product_id');
    }
}
