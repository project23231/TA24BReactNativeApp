<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->integer('booking_id');
            $table->integer('screening_id');
            $table->integer('seat_id');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['valid', 'cancelled']);
            $table->integer('hall_id');

            $table->foreign('booking_id')
                ->references('id')->on('bookings');

            $table->foreign('screening_id')
                ->references('id')->on('screenings');

            $table->foreign('seat_id')
                ->references('id')->on('seats');

            $table->foreign(['booking_id', 'screening_id'])
                ->references(['id', 'screening_id'])
                ->on('bookings');

            $table->foreign(['screening_id', 'hall_id'])
                ->references(['id', 'hall_id'])
                ->on('screenings');

            $table->foreign(['seat_id', 'hall_id'])
                ->references(['id', 'hall_id'])
                ->on('seats');
        });

        DB::statement("
            ALTER TABLE tickets
            ADD CONSTRAINT tickets_price_positive
            CHECK (price > 0)
        ");

        DB::statement("
            CREATE UNIQUE INDEX tickets_valid_seat_unique
            ON tickets (screening_id, seat_id)
            WHERE status = 'valid'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};