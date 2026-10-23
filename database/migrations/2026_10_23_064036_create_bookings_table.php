<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('primary_guest_id')
                ->constrained('guests')
                ->restrictOnDelete();

            $table->string('reference', 30)->unique();

            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);

            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('unpaid');

            $table->char('currency', 3)->default('PHP');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->text('special_requests')->nullable();

            $table->timestamps();

            $table->index(['location_id', 'status']);
            $table->index(['check_in', 'check_out']);
        });

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_valid_dates
            CHECK (check_out > check_in)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
