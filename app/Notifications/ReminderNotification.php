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
    public $user;

    public function __construct(Reminder $reminder, User $user)
    {
        $this->reminder = $reminder;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        \Log::info('Rendering email for Reminder ID: ' . $this->reminder->id . ' to: ' . $notifiable->email);
        // return (new MailMessage)
        //     ->subject('Reminder: ' . $this->reminder->title)
        //     ->greeting('Hello ' . $this->user->name . ',')
        //     ->line('You have a reminder for the following task:')
        //     ->line('**Title**: ' . $this->reminder->title)
        //     ->line('**Due Date**: ' . $this->reminder->due_date->format('Y-m-d H:i'))
        //     ->line('**Lead ID**: ' . $this->reminder->lead_id)
        //     ->line('**Status**: ' . ucfirst($this->reminder->status))
        //     ->action('View Lead', url('/sales/leads/' . $this->reminder->lead_id))
        //     ->line('Please take appropriate action to address this reminder.');
        return ; //disabled for dev
    }

    public function toArray($notifiable)
    {
        \Log::info('Storing database notification for Reminder ID: ' . $this->reminder->id);
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'due_date' => $this->reminder->due_date->format('Y-m-d H:i'),
            'lead_id' => $this->reminder->lead_id,
            'status' => $this->reminder->status,
        ];
    }
}
