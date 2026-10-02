<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A company that issues quotations and delivery orders (COLOSSAL MEDIA or COLOSSAL XCEED). */
class Company extends Model
{
    protected $fillable = ['code', 'name', 'reg_no', 'address', 'phone', 'fax', 'mobile', 'email', 'logo', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function default(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }

    /** Logo file on disk, or null when it is missing. */
    public function logoPath(): ?string
    {
        return $this->logo && is_file(public_path($this->logo)) ? public_path($this->logo) : null;
    }

    /** "Tel: … · Fax: … · Mobile: … · email", skipping blanks. */
    public function contactLine(): string
    {
        return collect([
            $this->phone ? "Tel: {$this->phone}" : null,
            $this->fax ? "Fax: {$this->fax}" : null,
            $this->mobile ? "Mobile: {$this->mobile}" : null,
            $this->email,
        ])->filter()->implode(' · ');
    }
}
