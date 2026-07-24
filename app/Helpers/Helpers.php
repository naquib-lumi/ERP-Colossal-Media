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

    private static function resolveActiveReceiver($receiver)
    {
        if (!$receiver) {
            return null;
        }

        if (!$receiver instanceof User) {
            return $receiver;
        }

        $receiverId = $receiver->getKey();

        if (!$receiverId) {
            return null;
        }

        return User::query()
            ->whereKey($receiverId)
            ->active()
            ->first();
    }

    public static function notify(
        $receiver,
        $message,
        $url,
        $via = ['database'],
        array $opts = []
    ) {
        $activeReceiver = self::resolveActiveReceiver($receiver);

        if (!$activeReceiver) {
            return;
        }

        $activeReceiver->notify(
            new \App\Notifications\GenericNotification(
                $message,
                $url,
                $via,
                $opts
            )
        );
    }

    public static function notifyReminder(
        \App\Models\User $receiver,
        \App\Models\Reminder $reminder,
        array $via = ['mail', 'database'],
        array $opts = []
    ) {
        $activeReceiver = self::resolveActiveReceiver(
            $receiver
        );

        if (!$activeReceiver) {
            return;
        }

        $activeReceiver->notify(
            new \App\Notifications\ReminderNotification(
                $reminder,
                $activeReceiver,
                $via,
                $opts
            )
        );
    }

    public static function notifyOnce(
        User $user,
        string $message,
        string $url,
        array $channels = ['database'],
        string $key = '',
        int $ttl = 120,
        array $opts = []
    ): void {
        $activeUser = self::resolveActiveReceiver($user);

        if (!$activeUser) {
            return;
        }

        $cacheKey =
            'notif_once:' .
            $activeUser->id .
            ':' .
            sha1($key);

        if (!Cache::add($cacheKey, 1, $ttl)) {
            return;
        }

        self::notify(
            $activeUser,
            $message,
            $url,
            $channels,
            $opts
        );
    }

}