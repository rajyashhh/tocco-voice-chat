<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->json('polygon_coordinates')->nullable()->after('phone_code');
            $table->json('covered_countries')->nullable()->after('polygon_coordinates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['polygon_coordinates', 'covered_countries']);
        });
    }
};
