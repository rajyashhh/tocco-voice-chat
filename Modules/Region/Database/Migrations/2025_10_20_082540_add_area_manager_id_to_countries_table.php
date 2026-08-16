<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedInteger('area_manager_id')->nullable()->after('id');

            $table->foreign('area_manager_id')
                ->references('id')
                ->on('admin_users')
                ->onDelete('set null'); 
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['area_manager_id']);
            $table->dropColumn('area_manager_id');
        });
    }
};
