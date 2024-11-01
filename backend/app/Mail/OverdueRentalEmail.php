<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OverdueRentalEmail extends Mailable
{
    public $item;
    public $rental;

    public function __construct($item, $rental)
    {
        $this->item = $item;
        $this->rental = $rental;
    }

    public function build()
    {
        return $this->view('emails.overdue_rental')
            ->with([
                'rental' => $this->rental,
                'itemName' => $this->rental->item_name,
            ]);
    }
}
