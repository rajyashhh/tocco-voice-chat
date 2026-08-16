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
        Schema::create('cp_level_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("vip_id");
            $table->unsignedBigInteger("item_id");
            $table->string("type");
            $table->string("sub_type");
            $table->string("gender");
            $table->string("expire");
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
        Schema::dropIfExists('cp_level_gifts');
    }
};
