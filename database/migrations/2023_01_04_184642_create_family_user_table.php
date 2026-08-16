<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFamilyUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('family_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('family_id')->index();
            $table->unsignedTinyInteger('user_type')->comment('Status 0=ordinary member 1=administrator 2=patriarch');
            $table->unsignedTinyInteger('status')->index()->comment('Status 0=pending 1=passed 2=rejected')->nullable();
            $table->unsignedTinyInteger('type')->comment('Type 0=Invite 1=Apply')->nullable();
            $table->unsignedInteger('ope_user_id')->comment('operator id')->nullable();
            $table->unsignedInteger('ope_time')->comment('operating time')->nullable();
            $table->unsignedTinyInteger('closeswitch')->comment('Whether to block family news 0=not block 1=block')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_user');
    }
}
