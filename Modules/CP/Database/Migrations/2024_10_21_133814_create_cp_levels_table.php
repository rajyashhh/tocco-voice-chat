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
        Schema::create('cp_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("cp_relation_id")->default(0);
            $table->integer("level");
            $table->integer("exp")->default(0);
            $table->string("name_en")->nullable();
            $table->string("name_ar")->nullable();
            $table->string("img")->nullable();
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
        Schema::dropIfExists('cp_levels');
    }
};
