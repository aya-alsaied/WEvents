<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignId('hall_availability_id')->constrained('hall_availabilities')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('booking_type', ['full_day', 'hourly']);
            $table->text('notes')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->decimal('admin_commission', 10, 2)->default(0);
            $table->decimal('provider_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'confirmed'])->default('pending');
            $table->enum('payment_status', [ 'unpaid', 'holding', 'paid','refunded'])->default('unpaid');
            $table->dateTime('payment_deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_bookings');
    }
};
