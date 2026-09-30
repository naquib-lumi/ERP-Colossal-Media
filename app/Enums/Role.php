<?php

namespace App\Enums;

/**
 * Every value stored in users.role. Use these instead of typing role strings,
 * e.g. $user->hasRole(Role::Boss) or Role::Boss->value in queries.
 */
enum Role: string
{
    case Admin                          = 'admin';
    case Boss                           = 'boss';
    case Salesperson                    = 'salesperson';
    case HeadSalesperson                = 'head-salesperson';
    case Artist                         = 'artist';
    case HeadArtist                     = 'head-artist';
    case DataEntry                      = 'data-entry';
    case OperationsPrinting             = 'operations-printing';
    case OperationsFurnishing           = 'operations-furnishing';
    case OperationsDeliveryInstallation = 'operations-delivery-installation';
    case OperationsDispatchControl      = 'operations-dispatch-control';

    public function label(): string
    {
        return match ($this) {
            self::Admin                          => 'Admin',
            self::Boss                           => 'Boss',
            self::Salesperson                    => 'Salesperson',
            self::HeadSalesperson                => 'Head Salesperson',
            self::Artist                         => 'Artist',
            self::HeadArtist                     => 'Head Artist',
            self::DataEntry                      => 'Data Entry',
            self::OperationsPrinting             => 'Operations - Printing',
            self::OperationsFurnishing           => 'Operations - Furnishing',
            self::OperationsDeliveryInstallation => 'Operations - Delivery & Installation',
            self::OperationsDispatchControl      => 'Operations - Dispatch Control',
        };
    }

    /** Named route each role lands on after login. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin                          => 'admin.dashboard',
            self::Boss                           => 'boss.dashboard',
            self::Salesperson,
            self::HeadSalesperson                => 'sales.dashboard',
            self::Artist,
            self::HeadArtist                     => 'artist.dashboard',
            self::DataEntry                      => 'data-entry.orders',
            self::OperationsPrinting             => 'printing.dashboard',
            self::OperationsFurnishing           => 'furnishing.dashboard',
            self::OperationsDeliveryInstallation => 'installation.dashboard',
            self::OperationsDispatchControl      => 'dispatchcontrol.dashboard',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return string[] */
    public static function sales(): array
    {
        return [self::Salesperson->value, self::HeadSalesperson->value];
    }

    /** @return string[] */
    public static function artists(): array
    {
        return [self::Artist->value, self::HeadArtist->value];
    }
}
