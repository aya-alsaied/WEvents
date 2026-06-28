<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('decoration_occasion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('decoration_id')
                ->constrained('decorations')
                ->onDelete('cascade');

            $table->foreignId('occasion_id')
                ->constrained('occasions')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decoration_occasion');
    }
};