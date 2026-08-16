<?php


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('additional_infos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agency_id');
            $table->string('gmail')->nullable();
            $table->string('face_image_nationalId')->nullable();
            $table->string('back_image_nationalId')->nullable();
            $table->bigInteger('country_id')->nullable();
            $table->text('history_app_info')->nullable();
            $table->integer('salary')->nullable();
            $table->integer('host')->nullable();
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
        Schema::dropIfExists('additional_infos');
    }
};
