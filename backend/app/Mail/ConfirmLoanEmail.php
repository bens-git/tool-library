<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Item;
use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmLoanEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $owner;
    public $renter;
    public $item;
    public $rental;

    public function __construct(User $renter, Item $item, Rental $rental)
    {

        $this->renter = $renter;
        $this->item = $item;
        $this->rental = $rental;
    }

    public function build()
    {
        return $this->subject('Item loaned')
            ->view('emails.loan');
    }
}
