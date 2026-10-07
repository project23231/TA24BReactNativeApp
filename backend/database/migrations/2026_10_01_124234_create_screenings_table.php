<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screenings', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->integer('movie_id');
            $table->integer('hall_id');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->decimal('price', 10, 2);
            $table->text('language');
            $table->text('subtitles')->nullable();
            $table->text('format');
            $table->enum('status', ['scheduled', 'cancelled']);

            $table->foreign('movie_id')
                ->references('id')
                ->on('movies');

            $table->foreign('hall_id')
                ->references('id')
                ->on('halls');

            $table->unique(['id', 'hall_id']);
        });

        DB::statement("
            ALTER TABLE screenings
            ADD CONSTRAINT screenings_price_positive
                CHECK (price > 0),
            ADD CONSTRAINT screenings_time_valid
                CHECK (ends_at > starts_at)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};