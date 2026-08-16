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
        Schema::table('users_joined_agencies', function (Blueprint $table) {
            if (!Schema::hasColumn('users_joined_agencies', 'kicked_by_app')) {
                $table->foreignId('kicked_by_app')->nullable();
            }

            if (!Schema::hasColumn('users_joined_agencies', 'kicked_by_admin')) {
                $table->foreignId('kicked_by_admin')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_joined_agencies', function (Blueprint $table) {
            if (Schema::hasColumn('users_joined_agencies', 'kicked_by_app')) {
                $table->dropColumn('kicked_by_app');
            }

            if (Schema::hasColumn('users_joined_agencies', 'kicked_by_admin')) {
                $table->dropColumn('kicked_by_admin');
            }
        });
    }
};
