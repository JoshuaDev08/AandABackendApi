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
        Schema::create('room_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rate_plan_id')
                ->constrained() 
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // ISO weekdays: Monday = 1, Sunday = 7.
            // null means every day.
            $table->jsonb('days_of_week')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'rate_plan_id',
                'is_active',
                'start_date',
                'end_date',
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_rates');
    }
};
