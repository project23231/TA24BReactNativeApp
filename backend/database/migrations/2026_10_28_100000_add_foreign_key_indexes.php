<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * PostgreSQL does not create indexes for foreign keys automatically.
 * These columns are queried by model relations (movie->screenings,
 * user->bookings, booking->tickets, ...) and checked when a parent
 * row is deleted, so without an index every such query scans the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenings', function (Blueprint $table) {
            $table->index('movie_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('screening_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('booking_id');
            $table->index('seat_id');
            // tickets_valid_seat_unique covers only status = 'valid'.
            $table->index('screening_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['screening_id']);
            $table->dropIndex(['seat_id']);
            $table->dropIndex(['booking_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['screening_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('screenings', function (Blueprint $table) {
            $table->dropIndex(['movie_id']);
        });
    }
};
