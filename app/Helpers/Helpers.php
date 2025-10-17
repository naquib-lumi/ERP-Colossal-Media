<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

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


      public static function notifyReminder($receiver, $message, $url, $via = ['database'])
    {
        $receiver->notify(new \App\Notifications\ReminderNotification($message, $url, $via));
    }

    public static function notifyOnce(User $user, string $message, string $url, array $channels = ['database'], string $key = '', int $ttl = 120): void
    {
        // build a stable key: per user + logical action
        $cacheKey = 'notif_once:' . $user->id . ':' . sha1($key);

        // Cache::add returns false if the key already exists
        if (!Cache::add($cacheKey, 1, $ttl)) {
            return; // already sent recently
        }

        // fall through to your existing method
        self::notify($user, $message, $url, $channels);
    }

}