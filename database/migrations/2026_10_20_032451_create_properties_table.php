<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            // Parent resort location
            $table->foreignId('location_id')
                ->constrained('locations')
                ->restrictOnDelete();

            // Property information
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Display
            $table->string('featured_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['location_id', 'slug']);
            $table->index(['location_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
