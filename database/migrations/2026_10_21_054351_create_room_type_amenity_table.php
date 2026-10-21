<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_type_amenity', function (Blueprint $table) {

            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();

            $table->foreignId('amenity_id')
                ->constrained('amenities')
                ->cascadeOnDelete();

            // Prevent duplicate amenity assignments
            $table->primary([
                'room_type_id',
                'amenity_id',
            ]);

            // Improve amenity-based lookup performance
            $table->index('amenity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_amenity');
    }
};
