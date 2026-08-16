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
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['sender_id', 'created_at', 'giftPrice']);
            $table->index(['receiver_id', 'created_at', 'giftPrice']);
            $table->index(['roomowner_id', 'created_at', 'giftPrice']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            //
        });
    }
};
