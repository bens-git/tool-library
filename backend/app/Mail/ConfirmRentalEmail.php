<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Item;
use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmRentalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $item;
    public $rental;

    public function __construct(User $user, Item $item, Rental $rental)
    {
        $this->user = $user;
        $this->item = $item;
        $this->rental = $rental;
    }

    public function build()
    {
        return $this->subject('Item rented')
                    ->view('emails.rent');
    }
}