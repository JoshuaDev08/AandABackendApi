<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            // Parent building or property
            $table->foreignId('property_id')
                ->constrained('properties')
                ->restrictOnDelete();

            // Accommodation information
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Guest capacity
            $table->unsignedSmallInteger('max_adults');
            $table->unsignedSmallInteger('max_children')->default(0);
            $table->unsignedSmallInteger('max_occupancy');

            // Room details
            $table->text('bed_configuration')->nullable();
            $table->decimal('size_sqm', 8, 2)->nullable();

            // Public visibility and ordering
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Slug is unique within the property
            $table->unique(['property_id', 'slug']);

            // Useful for property room listings
            $table->index([
                'property_id',
                'is_active',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
