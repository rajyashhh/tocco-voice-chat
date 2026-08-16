<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::table('vips', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('users', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('bans_rooms', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('bans', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('banners', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('wares', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });

    //     Schema::table('gifts', function (Blueprint $table) {
    //         $table->unsignedBigInteger('created_by')->nullable()->after('id');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('vips', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('bans_rooms', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('bans', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('banners', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('wares', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });

    //     Schema::table('gifts', function (Blueprint $table) {
    //         $table->dropColumn('created_by');
    //     });
    // }
};
