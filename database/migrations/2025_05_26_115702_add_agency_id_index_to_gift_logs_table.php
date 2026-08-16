<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index('agency_id'); // Add index
        });
    }

    public function down()
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropIndex(['agency_id']); // Rollback index
        });
    }
};
