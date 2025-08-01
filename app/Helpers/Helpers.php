<?php

namespace App\Helpers;

class Helpers
{
    public static function appClasses()
    {
        return config('custom.custom');
    }

    public static function generatePrimaryColorCSS($color)
    {
        return "body { --bs-primary: $color; }";
    }
}