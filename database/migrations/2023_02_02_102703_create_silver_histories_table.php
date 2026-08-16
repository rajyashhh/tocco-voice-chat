<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSilverHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('silver_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->double('coins')->nullable();
            $table->double('silvers')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('selver_id')->nullable();
            $table->unsignedBigInteger('charger_id')->nullable();
            $table->unsignedBigInteger('charger_type')->nullable();
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
        Schema::dropIfExists('silver_histories');
    }
}
