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
        Schema::create('messages', function (Blueprint $table) {
          $table->id();
            $table->string('room_code', 32);
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->string('sender_guest_token', 64)->nullable();
            $table->enum('type', ['text', 'image', 'video'])->default('text');
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
