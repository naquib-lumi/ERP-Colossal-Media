<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A quotation from COLOSSAL MEDIA or COLOSSAL XCEED to a customer (lead).
 * Amounts are typed by hand. Converted quotations are locked.
 */
class Quotation extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONVERTED = 'converted';

    // quotation_number, status, order_id and converted_at are set by the app, not by forms.
    protected $fillable = [
        'company_id', 'lead_id', 'lead_contact_id', 'attention', 'salesperson_id', 'created_by',
        'quotation_date', 'terms', 'po_number',
        'subtotal', 'discount', 'tax', 'grand_total', 'notes',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax'            => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'converted_at'   => 'datetime',
        'emailed_at'     => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Quotation $quotation) {
            $year = ($quotation->quotation_date ?? now())->format('Y');
            $quotation->quotation_number ??= DocumentSequence::nextQuotationNumber((int) $year);
            $quotation->status ??= self::STATUS_PENDING;
        });
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /** Converted quotations cannot be edited; revisions happen before an order is made. */
    public function isEditable(): bool
    {
        return ! $this->isConverted();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function products()
    {
        return $this->hasMany(QuotationProduct::class)->orderBy('sort')->orderBy('id');
    }
}
