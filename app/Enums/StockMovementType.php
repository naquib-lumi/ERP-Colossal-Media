<?php

namespace App\Enums;

/** Values stored in material_stock_movements.type. */
enum StockMovementType: string
{
    case Restock    = 'restock';    // stock received
    case Adjustment = 'adjustment'; // manual add or deduct (count correction, used up, damage, ...)

    public function label(): string
    {
        return match ($this) {
            self::Restock    => 'Restock',
            self::Adjustment => 'Adjustment',
        };
    }
}
