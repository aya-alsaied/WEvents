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
        Schema::create('public_party_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_party_id')->constrained('public_parties')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('tickets_count');
            $table->decimal('total_price', 10, 2);
            $table->decimal('admin_commission', 10, 2)->default(0);
            $table->decimal('provider_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'confirmed'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->dateTime('payment_deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_parties_bookings');
    }
};
