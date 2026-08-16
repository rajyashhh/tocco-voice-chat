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
        Schema::table('pk_winners', function (Blueprint $table) {
            $table->unique(['pk_event_id', 'user_id', 'pk_type'], 'pk_winners_event_user_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pk_winners', function (Blueprint $table) {
            $table->dropUnique('pk_winners_event_user_type_unique');
        });
    }
};