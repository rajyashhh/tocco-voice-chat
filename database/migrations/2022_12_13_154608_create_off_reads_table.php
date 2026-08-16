<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOffReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('off_reads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('off_id')->comment('official message id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('is_read')->default('1')->comment('Whether it has been read 1 has been read 2 has been deleted')->nullable();
            $table->unsignedInteger('addtime')->nullable();
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
        Schema::dropIfExists('off_reads');
    }
}
