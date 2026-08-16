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
        Schema::table('wares', function (Blueprint $table) {
            $table->renameColumn('type_img_profile', 'half_image_profile');
        });
    }

    public function down()
    {
        Schema::table('wares', function (Blueprint $table) {
            $table->renameColumn('half_image_profile', 'type_img_profile');
        });
    }
};
