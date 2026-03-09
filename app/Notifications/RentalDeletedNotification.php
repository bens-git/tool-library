<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsageDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $item,
        public $usage
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Usage Cancelled')
            ->greeting('Hello!')
            ->line("The usage for {$this->item->name} has been cancelled.")
            ->line("Usage ID: {$this->usage->id}")
            ->action('View Item', url(route('items.show', $this->item)))
            ->line('Thank you for using our platform!');
    }
}
