<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_images', function (Blueprint $table) {
            $table->id();

            // Images belong to accommodation types
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();

            // File storage
            $table->string('path');
            $table->string('alt_text')->nullable();

            // Gallery presentation
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'room_type_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_images');
    }
};
