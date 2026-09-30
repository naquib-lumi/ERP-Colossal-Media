<?php

namespace App\Enums;

/**
 * Every value allowed in orders.orderStatus (a MySQL ENUM column — adding a
 * case here also needs a migration that alters that column).
 */
enum OrderStatus: string
{
    case ToAssign      = 'to_assign';
    case Assigned      = 'assigned';
    case InProgress    = 'in_progress';
    case AwaitingKeyin = 'awaiting_keyin';
    case Pending       = 'pending';
    case Completed     = 'completed';
    case Rejected      = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::ToAssign      => 'Assign',
            self::Assigned      => 'Assigned',
            self::InProgress    => 'In Progress',
            self::AwaitingKeyin => 'Awaiting Key-in',
            self::Pending       => 'Pending',
            self::Completed     => 'Completed',
            self::Rejected      => 'Rejected',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
