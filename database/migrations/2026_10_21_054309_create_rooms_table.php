<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            // Parent accommodation type
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->restrictOnDelete();

            // Physical unit information
            $table->string('room_number', 50);
            $table->string('floor', 50)->nullable();

            // Operational status
            $table->string('status', 30)->default('available');

            // Staff-only information
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Each room number is unique within its room type
            $table->unique([
                'room_type_id',
                'room_number',
            ]);

            // Useful for filtering physical room inventory
            $table->index([
                'room_type_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
