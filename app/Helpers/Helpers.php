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

    public static function notify($receiver, $message, $url, $via = ['database'])
    {
        $receiver->notify(new \App\Notifications\GenericNotification($message, $url, $via));
    }

}