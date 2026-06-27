<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HallBooking;

class CancelExpiredHallBookings extends Command
{
    protected $signature = 'hall-bookings:cancel-expired';

    protected $description = 'Cancel expired unpaid hall bookings';

    public function handle()
    {
        $bookings = HallBooking::where('status', 'approved')
            ->where('payment_status', 'unpaid')
            ->where('payment_deadline', '<', now())
            ->get();

        foreach ($bookings as $booking) {

            $booking->update([
                'status' => 'cancelled'
            ]);

            $booking->availability()->update([
                'status' => 'available'
            ]);
        }

        $this->info('Expired bookings cancelled.');
    }
}