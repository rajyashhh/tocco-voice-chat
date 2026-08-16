<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('banner_country', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('home_carousel_id');
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banner_country');
    }
};
