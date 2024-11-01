<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Item;
use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmRentalDeletionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $item;
    public $rental;

    public function __construct( Item $item, Rental $rental)
    {
        $this->item = $item;
        $this->rental = $rental;
    }

    public function build()
    {
        return $this->subject('Rental deleted')
                    ->view('emails.delete');
    }
}