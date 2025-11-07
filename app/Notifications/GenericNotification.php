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
    public $opts;

    public function __construct($message, $url, $via = ['database'], array $opts = [])
    {
        $this->message = $message;
        $this->url = $url;
        $this->via = is_array($via) ? $via : [$via];
        $this->opts    = $opts;
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
    $subject     = $this->opts['subject']     ?? 'Update from ' . config('app.name');
    $title       = $this->opts['title']       ?? 'Notification';
    $intro       = $this->opts['intro']       ?? null;
    $cta         = $this->opts['cta']         ?? 'View Details';
    $salutation  = $this->opts['salutation']  ?? 'Regards,<br>' . e(config('app.name'));
    $previewText = $this->opts['previewText'] ?? null;
    $details     = $this->opts['details']     ?? [];   // [['label'=>'Order No', 'value'=>'ORD-2025-0166'], ...]
    $secondary   = $this->opts['secondary']   ?? null; // ['label'=>'Contact support','url'=>...]
    $logoUrl     = $this->opts['logoUrl']     ?? null; // optional /public/images/logo.png
    $brandColor  = $this->opts['brandColor']  ?? '#696cff';
    $headerStart = $this->opts['headerStart'] ?? '#696cff';
    $headerEnd   = $this->opts['headerEnd']   ?? '#696cff';
    $heroEmoji   = $this->opts['heroEmoji']   ?? '📬';

    return (new \Illuminate\Notifications\Messages\MailMessage)
        ->subject($subject)
        ->view('emails.notification', [
            'subject'     => $subject,
            'title'       => $title,
            'intro'       => $intro,
            'messageText' => $this->message,
            'ctaLabel'    => $cta,
            'ctaUrl'      => $this->url,
            'salutation'  => $salutation,
            'previewText' => $previewText,
            'details'     => $details,
            'secondary'   => $secondary,
            'logoUrl'     => $logoUrl,
            'brandColor'  => $brandColor,
            'headerStart' => $headerStart,
            'headerEnd'   => $headerEnd,
            'heroEmoji'   => $heroEmoji,
            'appName'     => config('app.name'),
        ]);
}

}