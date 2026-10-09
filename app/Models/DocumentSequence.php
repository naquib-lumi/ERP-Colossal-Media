<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Running numbers per document type and year. To continue the client's own
 * numbering, set last_number for the year (e.g. 252 makes the next quotation
 * ST26-0253).
 */
class DocumentSequence extends Model
{
    protected $fillable = ['key', 'year', 'last_number'];

    protected $casts = [
        'year'        => 'integer',
        'last_number' => 'integer',
    ];

    /** Next number for $key in $year, safe under concurrent requests. */
    public static function next(string $key, int $year): int
    {
        return DB::transaction(function () use ($key, $year) {
            $row = static::where('key', $key)->where('year', $year)->lockForUpdate()->first();

            if (! $row) {
                // insertOrIgnore copes with two requests creating the row at once.
                static::query()->insertOrIgnore(['key' => $key, 'year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $row = static::where('key', $key)->where('year', $year)->lockForUpdate()->first();
            }

            $row->last_number++;
            $row->save();

            return $row->last_number;
        });
    }

    /** ST + 2-digit year + 4-digit number, shared by both companies, e.g. ST26-0253. */
    public static function nextQuotationNumber(int $year): string
    {
        return sprintf('ST%02d-%04d', $year % 100, static::next('quotation', $year));
    }
}
