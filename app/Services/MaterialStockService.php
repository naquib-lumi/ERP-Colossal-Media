<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only place that changes a material's stock balances. Each change locks
 * the material row, updates the balances and writes a movement with the
 * balance after it, all in one transaction.
 *
 * Volume is in square inches (the unit of materials.unitCost). Stock may go
 * negative: orders are never blocked by stock, a negative balance is shown
 * as a warning instead.
 */
class MaterialStockService
{
    /** Stock volume is stored in square inches; screens show square feet. */
    public const SQ_IN_PER_SQ_FT = 144;

    public static function toSqFt(float|string|null $sqIn): float
    {
        return round((float) $sqIn / self::SQ_IN_PER_SQ_FT, 2);
    }

    public static function fromSqFt(float|string|null $sqFt): float
    {
        return (float) $sqFt * self::SQ_IN_PER_SQ_FT;
    }

    /**
     * @param  float  $quantityChange  + adds, - deducts (in the material's quantity_unit)
     * @param  float  $volumeChange    + adds, - deducts (square inches)
     * @param  array{order_id?: int|null, product_item_id?: int|null}  $refs
     */
    public function record(
        Material|int $material,
        StockMovementType $type,
        float $quantityChange,
        float $volumeChange,
        ?string $reason = null,
        ?int $userId = null,
        array $refs = [],
    ): MaterialStockMovement {
        $quantityChange = round($quantityChange, 2);
        $volumeChange   = round($volumeChange, 2);

        if ($quantityChange == 0 && $volumeChange == 0) {
            throw new InvalidArgumentException('A stock movement must change quantity or volume.');
        }

        $materialId = $material instanceof Material ? $material->getKey() : $material;

        return DB::transaction(function () use ($materialId, $type, $quantityChange, $volumeChange, $reason, $userId, $refs) {
            /** @var Material $locked */
            $locked = Material::whereKey($materialId)->lockForUpdate()->firstOrFail();

            $quantityAfter = round((float) $locked->stock_quantity + $quantityChange, 2);
            $volumeAfter   = round((float) $locked->stock_volume + $volumeChange, 2);

            // Balances are not mass assignable; set them directly.
            $locked->stock_quantity = $quantityAfter;
            $locked->stock_volume   = $volumeAfter;
            $locked->save();

            return MaterialStockMovement::create([
                'material_id'     => $locked->getKey(),
                'material_name'   => $locked->materialName,
                'type'            => $type,
                'quantity_change' => $quantityChange,
                'volume_change'   => $volumeChange,
                'quantity_after'  => $quantityAfter,
                'volume_after'    => $volumeAfter,
                'reason'          => $reason !== null ? mb_substr(trim($reason), 0, 500) : null,
                'order_id'        => $refs['order_id'] ?? null,
                'product_item_id' => $refs['product_item_id'] ?? null,
                'user_id'         => $userId,
            ]);
        });
    }
}
