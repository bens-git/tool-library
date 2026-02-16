<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $item,
        public $rental
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Rental Cancelled')
            ->greeting('Hello!')
            ->line("The rental for {$this->item->name} has been cancelled.")
            ->line("Rental ID: {$this->rental->id}")
            ->action('View Item', url(route('items.show', $this->item)))
            ->line('Thank you for using our platform!');
    }
}
