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
        if (!Schema::hasColumn('admin_rewards', 'package_id')) {
            Schema::table('admin_rewards', function (Blueprint $table) {
                $table->unsignedBigInteger('package_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_rewards', function (Blueprint $table) {
            $table->dropColumn('package_id');
        });
    }
};
