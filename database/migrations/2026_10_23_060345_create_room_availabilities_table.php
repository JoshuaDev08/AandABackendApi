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
        Schema::create('room_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            $table->string('status', 30);
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->index([
                'room_id',
                'start_date',
                'end_date',
            ]);
        });

        DB::statement("
            ALTER TABLE room_availabilities
            ADD CONSTRAINT room_availabilities_valid_dates
            CHECK (end_date > start_date)
        ");

        DB::statement("
            ALTER TABLE room_availabilities
            ADD CONSTRAINT room_availabilities_valid_status
            CHECK (status IN (
                'reserved',
                'occupied',
                'blocked',
                'maintenance'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_availabilities');
    }
};
