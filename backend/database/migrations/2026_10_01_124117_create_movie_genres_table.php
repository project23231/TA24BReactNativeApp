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
        Schema::create('movie_genres', function (Blueprint $table) {
            $table->integer('movie_id');
            $table->integer('genre_id');

            $table->primary(['movie_id', 'genre_id']);

            $table->foreign('movie_id')
                ->references('id')
                ->on('movies');

            $table->foreign('genre_id')
    ->references('id')
    ->on('genres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_genres');
    }
};
