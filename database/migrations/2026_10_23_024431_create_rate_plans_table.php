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
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('code', 60);

            $table->text('description')->nullable();

            $table->unsignedInteger('priority')->default(0);
            $table->unsignedSmallInteger('min_nights')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['room_type_id', 'code']);
            $table->index(['room_type_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
