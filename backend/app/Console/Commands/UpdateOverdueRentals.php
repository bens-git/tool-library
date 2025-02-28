<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OverdueRentalEmail;
use Illuminate\Support\Facades\Log;

class UpdateOverdueRentals extends Command
{
    protected $signature = 'rentals:update-overdue';
    protected $description = 'Update rental statuses to overdue if past end date';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get all rentals that are active and past the end date
        $overdueRentals = \App\Models\Rental::with(['renter']) // Eager load relationships
            ->where('status', 'active')
            ->where('ends_at', '<', Carbon::now())
            ->select([
                'rentals.*', // Select all rental fields
                DB::raw("
                CONCAT(
                    LOWER(REGEXP_REPLACE(LEFT(renters.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                    LOWER(REGEXP_REPLACE(LEFT(archetypes.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                    items.id
                ) AS item_name
            ")
            ])
            ->join('users as renters', 'rentals.rented_by', '=', 'renters.id') // Join with users table for renters
            ->join('items', 'rentals.item_id', '=', 'items.id') // Join with items table
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id') // Join with archetypes table
            ->get();

        foreach ($overdueRentals as $rental) {
            // Update the rental status to 'overdue'
            $rental->update(['status' => 'overdue']);
            Log::info($rental);

            // Send an email to the renter informing them that the rental is overdue
            Mail::to($rental->renter->email) // Using the renter's email
                ->send(new OverdueRentalEmail($rental->item, $rental));

            $this->info('Overdue rental email sent to ' . $rental->renter->email);
        }
    }
}
