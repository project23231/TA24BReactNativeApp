<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->index('release_date');
        });

        Schema::table('movie_genres', function (Blueprint $table) {
            $table->index(['genre_id', 'movie_id']);
        });

        Schema::table('halls', function (Blueprint $table) {
            $table->index('cinema_id');
        });

        Schema::table('screenings', function (Blueprint $table) {
            $table->index('starts_at');
            $table->index(['hall_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('screenings', function (Blueprint $table) {
            $table->dropIndex(['hall_id', 'starts_at']);
            $table->dropIndex(['starts_at']);
        });

        Schema::table('halls', function (Blueprint $table) {
            $table->dropIndex(['cinema_id']);
        });

        Schema::table('movie_genres', function (Blueprint $table) {
            $table->dropIndex(['genre_id', 'movie_id']);
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex(['release_date']);
        });
    }
};