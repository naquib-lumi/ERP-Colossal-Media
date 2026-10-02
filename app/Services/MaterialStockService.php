<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Helpers\Helpers;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * The only place that changes a material's stock. Each change locks the
 * material row, updates the balance and writes a movement with the balance
 * after it, all in one transaction.
 *
 * Stock is a whole number of units; what 1 unit is (roll, sheet, piece, box)
 * is set per material in quantity_unit. Stock may go negative: it is shown
 * as a warning, never blocked.
 *
 * When a change takes the stock from above the material's alert level to at
 * or below it, every active admin gets an email and an in-system notification.
 * Further deductions while it is already low do not alert again.
 */
class MaterialStockService
{
    /** @param  int  $quantityChange  + adds, - deducts (whole units) */
    public function record(
        Material|int $material,
        StockMovementType $type,
        int $quantityChange,
        ?string $reason = null,
        ?int $userId = null,
    ): MaterialStockMovement {
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('A stock movement must change the quantity.');
        }

        $materialId = $material instanceof Material ? $material->getKey() : $material;

        [$movement, $locked, $before] = DB::transaction(function () use ($materialId, $type, $quantityChange, $reason, $userId) {
            /** @var Material $locked */
            $locked = Material::whereKey($materialId)->lockForUpdate()->firstOrFail();

            $before = (int) $locked->stock_quantity;
            $after  = $before + $quantityChange;

            // The balance is not mass assignable; set it directly.
            $locked->stock_quantity = $after;
            $locked->save();

            $movement = MaterialStockMovement::create([
                'material_id'     => $locked->getKey(),
                'material_name'   => $locked->materialName,
                'type'            => $type,
                'quantity_change' => $quantityChange,
                'quantity_after'  => $after,
                'reason'          => $reason !== null ? mb_substr(trim($reason), 0, 500) : null,
                'user_id'         => $userId,
            ]);

            return [$movement, $locked, $before];
        });

        if ($locked->low_stock_quantity !== null
            && $before > $locked->low_stock_quantity
            && $locked->stock_quantity <= $locked->low_stock_quantity) {
            $this->alertLowStock($locked);
        }

        return $movement;
    }

    /** Email + in-system notification to every active admin. A failed email never undoes the stock change. */
    private function alertLowStock(Material $material): void
    {
        $unit    = $material->quantity_unit ?: 'unit(s)';
        $message = "Low stock: {$material->materialName} has {$material->stock_quantity} {$unit} left (alert level {$material->low_stock_quantity}).";
        $url     = route('inventory.show', $material);

        $admins = User::where('role', Role::Admin->value)->active()->get();
        foreach ($admins as $admin) {
            try {
                Helpers::notify($admin, $message, $url, ['database', 'mail'], [
                    'subject'   => "Low stock: {$material->materialName}",
                    'title'     => 'Low stock alert',
                    'cta'       => 'Open inventory',
                    'heroEmoji' => '📦',
                    'details'   => [
                        ['label' => 'Material', 'value' => $material->materialName],
                        ['label' => 'Stock left', 'value' => "{$material->stock_quantity} {$unit}"],
                        ['label' => 'Alert level', 'value' => "{$material->low_stock_quantity} {$unit}"],
                    ],
                ]);
            } catch (Throwable $e) {
                logger()->error('Low stock alert failed', ['material_id' => $material->getKey(), 'user_id' => $admin->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
