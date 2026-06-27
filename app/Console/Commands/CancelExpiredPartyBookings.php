<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PublicPartyBooking;

class CancelExpiredPartyBookings extends Command
{
    protected $signature = 'party:cancel-expired';

    protected $description = 'Cancel expired party bookings';

    public function handle()
    {
        $bookings = PublicPartyBooking::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->where('payment_deadline', '<', now())
            ->get();

        foreach ($bookings as $booking) {

            $booking->publicParty->increment(
                'tickets',
                $booking->tickets_count
            );

            $booking->update([
                'status' => 'cancelled'
            ]);
        }

        $this->info('Expired party bookings cancelled.');

        return self::SUCCESS;
    }
}