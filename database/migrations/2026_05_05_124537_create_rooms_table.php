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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 32)->unique();
            $table->enum('type', ['video', 'audio', 'message']);
            $table->unsignedBigInteger('user1_id')->nullable();
            $table->string('user1_guest_token', 64)->nullable();
            $table->unsignedBigInteger('user2_id')->nullable();
            $table->string('user2_guest_token', 64)->nullable();
            $table->enum('status', ['waiting', 'active', 'ended'])->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
