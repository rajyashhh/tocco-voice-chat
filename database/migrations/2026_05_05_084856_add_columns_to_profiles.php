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
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('avatar_thumb')->nullable();
            $table->string('avatar_medium')->nullable();
            $table->string('avatar_large')->nullable();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('avatar_thumb')->nullable();
            $table->string('avatar_medium')->nullable();
            $table->string('avatar_large')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['avatar_thumb', 'avatar_medium', 'avatar_large']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['avatar_thumb', 'avatar_medium', 'avatar_large']);
        });
    }
};
