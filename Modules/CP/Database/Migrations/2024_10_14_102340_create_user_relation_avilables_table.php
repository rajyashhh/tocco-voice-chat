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
        Schema::create('user_relation_avilables', function (Blueprint $table) {
            $table->id();
            $table->foreignId ('user_id')->constrained('users')->onDelete ('cascade');
            $table->unsignedBigInteger("cp_relation_id")->default(0);
            $table->integer("count")->default(0);
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
        Schema::dropIfExists('user_relation_avilables');
    }
};
