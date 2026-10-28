<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deleting a movie or a genre removes its genre links.
        // Other foreign keys keep the default NO ACTION on purpose:
        // bookings and tickets are sales history and must not disappear.
        Schema::table('movie_genres', function (Blueprint $table) {
            $table->dropForeign(['movie_id']);
            $table->dropForeign(['genre_id']);

            $table->foreign('movie_id')
                ->references('id')
                ->on('movies')
                ->cascadeOnDelete();

            $table->foreign('genre_id')
                ->references('id')
                ->on('genres')
                ->cascadeOnDelete();
        });

        // Two halls with the same name in one cinema are not allowed.
        Schema::table('halls', function (Blueprint $table) {
            $table->unique(['cinema_id', 'name']);
        });

        // Time of cancellation, needed for refunds and reports.
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestampTz('cancelled_at')->nullable();
        });

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT movies_age_rating_valid
            CHECK (age_rating IN ('0+', '6+', '12+', '16+', '18+'))
        ");

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_customer_email_format
                CHECK (customer_email LIKE '%_@_%'),
            ADD CONSTRAINT bookings_cancelled_at_valid
                CHECK (cancelled_at IS NULL OR status = 'cancelled')
        ");

        // Two scheduled screenings in the same hall must not overlap in time.
        // btree_gist lets a GiST index compare hall_id with = next to a time range.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement("
            ALTER TABLE screenings
            ADD CONSTRAINT screenings_no_overlap
            EXCLUDE USING gist (
                hall_id WITH =,
                tstzrange(starts_at, ends_at) WITH &&
            )
            WHERE (status = 'scheduled')
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE screenings DROP CONSTRAINT screenings_no_overlap');

        DB::statement('
            ALTER TABLE bookings
            DROP CONSTRAINT bookings_cancelled_at_valid,
            DROP CONSTRAINT bookings_customer_email_format
        ');

        DB::statement('ALTER TABLE movies DROP CONSTRAINT movies_age_rating_valid');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });

        Schema::table('halls', function (Blueprint $table) {
            $table->dropUnique(['cinema_id', 'name']);
        });

        Schema::table('movie_genres', function (Blueprint $table) {
            $table->dropForeign(['movie_id']);
            $table->dropForeign(['genre_id']);

            $table->foreign('movie_id')->references('id')->on('movies');
            $table->foreign('genre_id')->references('id')->on('genres');
        });
    }
};
