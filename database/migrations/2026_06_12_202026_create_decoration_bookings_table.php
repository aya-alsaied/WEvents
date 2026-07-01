<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decoration_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decoration_id')->constrained('decorations')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('event_date');
            $table->time('event_time');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('admin_commission', 10, 2)->default(0);
            $table->decimal('provider_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'confirmed'])->default('pending');
            $table->enum('payment_status', [ 'unpaid', 'paid'])->default('unpaid');
            $table->dateTime('payment_deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_bookings');
    }
};
