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
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('theme')->nullable();
            $table->json('pic')->nullable();
            $table->json('about')->nullable();
            $table->json('services_data')->nullable();
            $table->json('public_events')->nullable();
            $table->json('recent')->nullable();
            $table->json('benefits')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};