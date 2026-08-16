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
        Schema::dropIfExists('cps');

        Schema::create('cps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("cp_relation_id")->default(0);
            $table->foreignId ('user_one_id')->constrained('users')->onDelete ('cascade');
            $table->foreignId ('user_two_id')->constrained('users')->onDelete ('cascade');
            $table->integer("status")->default(0)->comment("pending=>0,accepted=>1,refused=>2,stop=>3,restore=>4,pending_restore=>5");
            $table->integer('di')->default(0);
            $table->unsignedBigInteger("level_id")->default(0);
            $table->double("price")->default(0);
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
        Schema::dropIfExists('cps');
    }
};
