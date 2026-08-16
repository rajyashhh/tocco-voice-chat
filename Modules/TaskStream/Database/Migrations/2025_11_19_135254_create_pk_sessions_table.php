<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePkSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pk_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_stream_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('team_1')->default('0,0');
            $table->string('team_2')->default('0,0');
            $table->boolean('status')->default(0);
            $table->tinyInteger('winner')->nullable();
            $table->double('team_1_score')->nullable();
            $table->double('team_2_score')->nullable();
            $table->dateTime('ends_at');
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
        Schema::dropIfExists('pk_sessions');
    }
}
