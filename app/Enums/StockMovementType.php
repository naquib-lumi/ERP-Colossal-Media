<?php

namespace App\Enums;

/** Values stored in material_stock_movements.type. */
enum StockMovementType: string
{
    case Restock     = 'restock';      // stock received
    case Adjustment  = 'adjustment';   // manual add or deduct (count correction, damage, ...)
    case OrderDeduct = 'order_deduct'; // used by a submitted order item
    case OrderReturn = 'order_return'; // given back when an order item changes or is rejected

    public function label(): string
    {
        return match ($this) {
            self::Restock     => 'Restock',
            self::Adjustment  => 'Adjustment',
            self::OrderDeduct => 'Used by order',
            self::OrderReturn => 'Returned from order',
        };
    }
}
