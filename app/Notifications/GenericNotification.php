<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class GenericNotification extends Notification
{
    use Queueable;

    public $message;
    public $url;
    public $via;

    public function __construct($message, $url, $via = ['database'])
    {
        $this->message = $message;
        $this->url = $url;
        $this->via = is_array($via) ? $via : [$via];
    }

    public function via($notifiable)
    {
        return $this->via;
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Notification')
            ->line($this->message)
            ->action('View Details', $this->url);
    }
}