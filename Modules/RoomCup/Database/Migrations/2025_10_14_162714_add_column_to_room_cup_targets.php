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
        if (Schema::hasTable('room_cup_targets')) {
            Schema::table('room_cup_targets', function (Blueprint $table) {
                if (!Schema::hasColumn('room_cup_targets', 'total_profit')) {
                    $table->decimal('total_profit', 8, 2)->default(0);
                }
                if (!Schema::hasColumn('room_cup_targets', 'owner_percentage')) {
                    $table->integer('owner_percentage')->default(0);
                }
                if (!Schema::hasColumn('room_cup_targets', 'admin_percentage')) {
                    $table->integer('admin_percentage')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('room_cup_targets')) {
            Schema::table('room_cup_targets', function (Blueprint $table) {
                if (Schema::hasColumn('room_cup_targets', 'total_profit')) {
                    $table->dropColumn('total_profit');
                }
                if (Schema::hasColumn('room_cup_targets', 'owner_percentage')) {
                    $table->dropColumn('owner_percentage');
                }
                if (Schema::hasColumn('room_cup_targets', 'admin_percentage')) {
                    $table->dropColumn('admin_percentage');
                }
            });
        }
    }
};
