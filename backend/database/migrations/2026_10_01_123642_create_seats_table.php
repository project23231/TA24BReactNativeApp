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
        Schema::create('seats', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->integer('hall_id');
            $table->integer('row_number');
            $table->integer('seat_number');

            $table->foreign('hall_id')
                ->references('id')
                ->on('halls');

            $table->unique(['hall_id', 'row_number', 'seat_number']);
            $table->unique(['id', 'hall_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
