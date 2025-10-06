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

    public $reminder;
    public $recipient;
    public $via;

    public function __construct(Reminder $reminder, User $recipient, array $via = ['mail', 'database'])
    {
        $this->reminder = $reminder;
        $this->recipient = $recipient;
        $this->via = $via;
    }

    public function via($notifiable)
    {
        return $this->via;
    }

    public function toMail($notifiable)
    {
        \Log::info('Rendering email for Reminder ID: ' . $this->reminder->id . ' to: ' . $notifiable->email);
        return (new MailMessage)
            ->subject('Reminder: ' . $this->reminder->title)
            ->greeting('Hello ' . $this->recipient->name . ',')
            ->line('You have a reminder for the following task:')
            ->line('**Title**: ' . $this->reminder->title)
            ->line('**Remind At**: ' . $this->reminder->remind_at->format('Y-m-d H:i'))
            ->line('**Lead ID**: ' . $this->reminder->lead_id)
            ->line('**Status**: ' . ucfirst($this->reminder->status))
            ->action('View Lead', url('/leads/' . $this->reminder->lead_id))
            ->line('Please take appropriate action to address this reminder.');
    }

    public function toArray($notifiable)
    {
        \Log::info('Storing database notification for Reminder ID: ' . $this->reminder->id);
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'remind_at' => $this->reminder->remind_at->format('Y-m-d H:i'),
            'lead_id' => $this->reminder->lead_id,
            'status' => $this->reminder->status,
        ];
    }
}