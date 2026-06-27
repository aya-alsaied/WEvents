<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DecorationBooking;

class CancelExpiredDecorationBookings extends Command
{
    protected $signature = 'decoration:cancel-expired';

    protected $description = 'Cancel decoration bookings whose payment deadline has expired';

    public function handle()
    {
        $count = DecorationBooking::where('status', 'approved')
            ->where('payment_status', 'unpaid')
            ->where('payment_deadline', '<', now())
            ->update([
                'status' => 'cancelled'
            ]);

        $this->info("{$count} decoration bookings cancelled.");

        return self::SUCCESS;
    }
}