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
        Schema::table('streaming_analytics', function (Blueprint $table) {
            $table->unsignedInteger('total_messages')->default(0)->after('tracks_audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streaming_analytics', function (Blueprint $table) {
            $table->dropColumn('total_messages');
        });
    }
};
