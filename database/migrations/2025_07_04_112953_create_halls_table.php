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
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['inside', 'outside']);
            $table->integer('CapacityOfPeople');
            $table->string('location');
            $table->decimal('full_day_price', 10, 2);
            $table->decimal('hour_price', 10, 2);
            $table->longText('information');
            $table->longText('rules');
            $table->longText('images');
            $table->integer('buffer_minutes')->default(60);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
