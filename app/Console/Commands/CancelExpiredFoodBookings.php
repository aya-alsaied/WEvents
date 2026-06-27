<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FoodBooking;

class CancelExpiredFoodBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'food:cancel-expired';

    /**
     * The console command description.
     */
    protected $description = 'Cancel approved food bookings that were not paid before the payment deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cancelledCount = FoodBooking::where('status', 'approved')
            ->where('payment_status', 'unpaid')
            ->where('payment_deadline', '<', now())
            ->update([
                'status' => 'cancelled'
            ]);

        $this->info("{$cancelledCount} expired booking(s) cancelled.");

        return self::SUCCESS;
    }
}