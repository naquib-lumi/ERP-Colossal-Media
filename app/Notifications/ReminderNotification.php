<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReminderNotification extends Notification
{
    use Queueable;

    /** @var \App\Models\Reminder */
    public $reminder;

    /** @var \App\Models\User */
    public $recipient;

    /** @var array */
    public $via;

    /** @var array */
    public $opts;

    /**
     * @param  Reminder  $reminder
     * @param  User      $recipient
     * @param  array     $via        e.g. ['mail','database']
     * @param  array     $opts       e.g. ['subject'=>'..','cta'=>'..','greeting'=>'..','previewText'=>'..','logoUrl'=>'..','brandColor'=>'#2563EB']
     */
    public function __construct(Reminder $reminder, User $recipient, array $via = ['mail','database'], array $opts = [])
    {
        $this->reminder  = $reminder;
        $this->recipient = $recipient;
        $this->via       = $via;
        $this->opts      = $opts;
    }

    public function via($notifiable)
    {
        return $this->via;
    }

    public function toMail($notifiable)
    {
        \Log::info('Rendering reminder email', ['reminder_id' => $this->reminder->id, 'email' => $notifiable->email]);

        // ----- Defaults (can be overridden via $opts) -----
        $subject     = $this->opts['subject']     ?? ('Reminder: ' . (string) $this->reminder->title);
        $title       = $this->opts['title']       ?? 'Action Required';
        $intro       = $this->opts['intro']       ?? 'A friendly reminder so you don’t miss this.';
        $greeting    = $this->opts['greeting']    ?? ('Hello ' . ($this->recipient->name ?? 'there') . ',');
        $cta         = $this->opts['cta']         ?? 'Open Lead';
        $salutation  = $this->opts['salutation']  ?? ('Regards,<br>' . e(config('app.name')));
        $previewText = $this->opts['previewText'] ?? strip_tags((string) $this->reminder->title);

        $logoUrl     = $this->opts['logoUrl']     ?? null;
        $brandColor  = $this->opts['brandColor']  ?? '#696cff'; // amber for reminders
        $headerStart = $this->opts['headerStart'] ?? '#696cff';
        $headerEnd   = $this->opts['headerEnd']   ?? '#696cff';
        $heroEmoji   = $this->opts['heroEmoji']   ?? '⏰';

        // Safe date formatting
        $remindAt = $this->reminder->remind_at;
        if (is_string($remindAt)) {
            try { $remindAt = \Carbon\Carbon::parse($remindAt); } catch (\Throwable $e) {}
        }
        $remindAtStr = $remindAt instanceof \Carbon\Carbon
            ? $remindAt->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i')
            : (string) $this->reminder->remind_at;

        // Details list for the email’s facts table
        $details = [
            ['label' => 'Title',     'value' => (string) $this->reminder->title],
            ['label' => 'Remind At', 'value' => $remindAtStr],
            ['label' => 'Lead ID',   'value' => (string) $this->reminder->lead_id],
            ['label' => 'Status',    'value' => ucfirst((string) $this->reminder->status)],
        ];

        $url = url('/leads/' . $this->reminder->lead_id);

       $message = (new MailMessage)
        ->subject($subject)
        ->view('emails.reminder', [
            'subject'     => $subject,
            'title'       => $title,
            'intro'       => $intro,
            'messageText' => 'You have a reminder for the following task.',
            'ctaLabel'    => $cta,
            'ctaUrl'      => $url,
            'salutation'  => $salutation,
            'previewText' => $previewText,
            'details'     => $details,
            'logoUrl'     => $logoUrl,
            'brandColor'  => $brandColor,
            'headerStart' => $headerStart,
            'headerEnd'   => $headerEnd,
            'heroEmoji'   => $heroEmoji,
            'appName'     => config('app.name'),
        ]);

    if (app()->environment('local')) {
        $message->to('naquib@lumimarketing.com.my');
    }

    return $message;
    }
            
    

    public function toArray($notifiable)
    {
        \Log::info('Storing reminder notification', ['reminder_id' => $this->reminder->id]);

        $remindAt = $this->reminder->remind_at;
        if ($remindAt instanceof \Carbon\Carbon) {
            $remindAt = $remindAt->format('Y-m-d H:i');
        }

        return [
            'type'       => 'reminder',
            'reminder_id'=> (int) $this->reminder->id,
            'title'      => (string) $this->reminder->title,
            'remind_at'  => (string) $remindAt,
            'lead_id'    => (string) $this->reminder->lead_id,
            'status'     => (string) $this->reminder->status,
            'url'        => url('/leads/' . $this->reminder->lead_id),
        ];
    }
}
