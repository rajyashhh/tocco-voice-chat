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
        Schema::create('weekly_cp_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_cp_id')->nullable()->constrained('weekly_stars')->nullOnDelete();
            $table->integer('level');
            $table->string("target");
            $table->string("type");
            $table->string("sub_type")->nullable();
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
        Schema::dropIfExists('weekly_cp_gifts');
    }
};
