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
            $table->unsignedInteger('number_of_fans')->default(0);
            $table->unsignedInteger('number_of_followings')->default(0);
            $table->unsignedInteger('number_of_friends')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['number_of_fans', 'number_of_followings', 'number_of_friends']);
        });
    }
};
