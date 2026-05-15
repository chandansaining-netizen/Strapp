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
        Schema::create('guest_sessions', function (Blueprint $table) {
           $table->id();
            $table->string('session_token', 64)->unique();
            $table->string('display_name')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
