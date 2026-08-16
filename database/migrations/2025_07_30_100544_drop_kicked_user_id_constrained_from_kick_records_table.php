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
        Schema::table('kick_records', function (Blueprint $table) {
            $table->dropForeign(['kicked_user_id']);
            $table->string('type')->default('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kick_records', function (Blueprint $table) {
            //
        });
    }
};
