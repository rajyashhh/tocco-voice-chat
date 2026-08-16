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
        Schema::table('official_messages', function (Blueprint $table) {
           $table->string('admin_role')->nullable();
           $table->unsignedInteger('admin_role_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('official_messages', function (Blueprint $table) {
            $table->dropColumn('admin_role');
            $table->dropColumn('admin_role_id');
        });
    }
};
