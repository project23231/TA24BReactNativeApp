<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->text('title');
            $table->text('description');
            $table->integer('duration_minutes');
            $table->date('release_date')->nullable();
            $table->text('age_rating');
            $table->text('poster_url')->nullable();
            $table->text('trailer_url')->nullable();
        });

        DB::statement("
            ALTER TABLE movies
            ADD CONSTRAINT movies_duration_positive
            CHECK (duration_minutes > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};