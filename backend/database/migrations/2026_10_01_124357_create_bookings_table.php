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
        Schema::create('bookings', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->integer('user_id')->nullable();
            $table->integer('screening_id');
            $table->text('customer_email');
            $table->enum('status', ['confirmed', 'cancelled']);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('screening_id')
                ->references('id')
                ->on('screenings');

            $table->unique(['id', 'screening_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
