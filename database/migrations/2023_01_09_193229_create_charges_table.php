<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger ('charger_id');
            $table->string ('charger_type');
            $table->unsignedInteger ('user_id');
            $table->string ('user_type');
            $table->decimal ('amount',18,2)->nullable ()->default (0);
            $table->unsignedTinyInteger ('amount_type')->nullable ()->default (1)->comment ('1=diamonds 2=coins 3=gold 4=flowers');
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
        Schema::dropIfExists('charges');
    }
}
