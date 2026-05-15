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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->after('email');
            $table->string('display_name')->nullable()->after('name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('display_name');
            $table->boolean('is_online')->default(false)->after('gender');
            $table->timestamp('last_active')->nullable()->after('is_online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn(['role','display_name','gender','is_online','last_active']);
        });
    }
};
