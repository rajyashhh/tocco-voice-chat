<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToFamilyLevels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('family_levels', function (Blueprint $table) {
            $table->unsignedInteger ('members')->nullable ()->default (0);
            $table->unsignedInteger ('admins')->nullable ()->default (0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('family_levels', function (Blueprint $table) {
            //
        });
    }
}
