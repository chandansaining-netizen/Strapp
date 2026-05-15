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
        Schema::create('waiting_users', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['video', 'audio', 'message']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_token', 64)->nullable();
            $table->string('socket_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_users');
    }
};
