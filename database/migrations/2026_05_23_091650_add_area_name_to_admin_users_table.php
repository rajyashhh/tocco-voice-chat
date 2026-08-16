<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Add missing columns for area manager creation
     */
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'area_name')) {
                $table->string('area_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('admin_users', 'covered_countries')) {
                $table->json('covered_countries')->nullable()->after('area_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (Schema::hasColumn('admin_users', 'area_name')) {
                $table->dropColumn('area_name');
            }
            if (Schema::hasColumn('admin_users', 'covered_countries')) {
                $table->dropColumn('covered_countries');
            }
        });
    }
};
