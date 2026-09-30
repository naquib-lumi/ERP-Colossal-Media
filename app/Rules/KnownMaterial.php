<?php

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * An order item's material must be a material from the materials list
 * (matched by name, ignoring case and surrounding spaces). Names already
 * saved on this order's items stay allowed, so orders that use a material
 * which was later renamed away or removed can still be saved.
 */
class KnownMaterial implements ValidationRule
{
    /** @var array<string, true>|null lower-cased allowed names */
    private ?array $allowed = null;

    public function __construct(private ?Order $order = null)
    {
    }

    public static function normalise(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $key = self::normalise(is_scalar($value) ? (string) $value : '');
        if ($key === '') {
            return; // blanks are dropped when saving
        }

        if (! isset($this->allowedNames()[$key])) {
            $fail('":input" is not in the materials list. Ask an admin to add it first.');
        }
    }

    private function allowedNames(): array
    {
        if ($this->allowed !== null) {
            return $this->allowed;
        }

        $names = DB::table('materials')->pluck('materialName')->all();

        if ($this->order) {
            $itemMaterials = DB::table('product_items as pi')
                ->join('products as p', 'p.ProductID', '=', 'pi.ProductID')
                ->where('p.OrderID', $this->order->id)
                ->whereNotNull('pi.material')
                ->pluck('pi.material');

            foreach ($itemMaterials as $json) {
                foreach ((array) json_decode((string) $json, true) as $name) {
                    if (is_string($name)) {
                        $names[] = $name;
                    }
                }
            }
        }

        $this->allowed = [];
        foreach ($names as $name) {
            $key = self::normalise($name);
            if ($key !== '') {
                $this->allowed[$key] = true;
            }
        }

        return $this->allowed;
    }
}
