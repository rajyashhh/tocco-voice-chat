<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id')->comment('صاحب الوكالة');
            $table->string('name')->comment('اسم الوكالة');
            $table->string('notice')->default('notice')->comment('جملة الترحيب')->nullable();
            $table->unsignedTinyInteger('status')->default('1');
            $table->string('phone')->nullable();
            $table->string('url')->nullable();
            $table->string('img')->nullable();
            $table->text('contents')->nullable();
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
        Schema::dropIfExists('agencies');
    }
}
