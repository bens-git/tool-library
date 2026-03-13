<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPrivateMessageNotification extends Notification
{


    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $message,
        public $sender
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
        // Get a preview of the message body (truncated if too long)
        $messagePreview = strlen($this->message->body) > 100 
            ? substr($this->message->body, 0, 100) . '...' 
            : $this->message->body;

        return (new MailMessage)
            ->subject('New Private Message')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new private message from ' . $this->sender->name . '.')
            ->line('Message preview: ' . $messagePreview)
            ->action('View Message', url('/messages'))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'body' => $this->message->body,
        ];
    }
}

