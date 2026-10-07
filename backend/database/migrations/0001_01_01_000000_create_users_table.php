<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->generatedAs('')->primary();
            $table->text('name');
            $table->text('email')->unique();
            $table->text('password_hash');
        
            $table->enum('role', ['admin', 'customer']);
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT user_role_valid
            CHECK (role IN ('admin', 'customer'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};