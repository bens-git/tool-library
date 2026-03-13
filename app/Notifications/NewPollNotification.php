<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPollNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $poll,
        public $message
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Poll Created')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new poll has been created in the community feed.')
            ->line('Question: ' . $this->poll->question)
            ->action('View Poll', url('/community'))
            ->line('Thank you for being part of our community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'poll_id' => $this->poll->id,
            'message_id' => $this->message->id,
            'question' => $this->poll->question,
        ];
    }
}

