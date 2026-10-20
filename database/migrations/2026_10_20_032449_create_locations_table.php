<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name');
            $table->string('slug')->unique();

            $table->string('short_description')->nullable();
            $table->text('description')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country')->default('Philippines');

            // Geographic location
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Contact information
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            // Resort policies
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('timezone')->default('Asia/Manila');

            // Display
            $table->string('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
