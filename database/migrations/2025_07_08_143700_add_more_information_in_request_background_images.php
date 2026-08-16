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
        Schema::table('request_background_images', function (Blueprint $table) {
            if (!Schema::hasColumn('request_background_images', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('id');
            }

            if (!Schema::hasColumn('request_background_images', 'created_by_type')) {
                $table->string('created_by_type')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('request_background_images', 'room_id')) {
                $table->unsignedInteger('room_id')->nullable()->after('owner_room_id');

                $table->foreign('room_id')
                    ->references('id')
                    ->on('rooms')
                    ->onDelete('set null');
            }

            // Only add index if both columns exist
            if (
                Schema::hasColumn('request_background_images', 'created_by') &&
                Schema::hasColumn('request_background_images', 'created_by_type')
            ) {
                $table->index(['created_by', 'created_by_type'], 'request_bg_created_by_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_background_images', function (Blueprint $table) {
            // Drop index safely
            $table->dropIndex('request_bg_created_by_index');

            // Drop created_by and created_by_type columns if they exist
            if (Schema::hasColumn('request_background_images', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('request_background_images', 'created_by_type')) {
                $table->dropColumn('created_by_type');
            }

            if (Schema::hasColumn('request_background_images', 'room_id')) {
                $table->dropForeign(['room_id']);
                $table->dropColumn('room_id');
            }
        });
    }
};
