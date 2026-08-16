<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pks', function (Blueprint $table) {
            $table->id();
            $table->integer ('team_1_boss')->nullable ();
            $table->string ('team_1')->nullable ()->default ('0,0,0,0');
            $table->integer ('team_2_boss')->nullable ();
            $table->string ('team_2')->nullable ('0,0,0,0');
            $table->integer ('judge')->nullable ();
            $table->unsignedTinyInteger ('status')->nullable ()->default (0);
            $table->double ('prize_value')->nullable ()->default (0);
            $table->unsignedBigInteger ('room_id')->nullable ()->default (0);
            $table->dateTime ('start_at')->nullable ();
            $table->dateTime ('end_at')->nullable ();
            $table->string ('winner')->nullable ();
            $table->string ('title')->nullable ();
            $table->string ('team_1_title')->nullable ();
            $table->string ('team_2_title')->nullable ();
            $table->text ('conditions')->nullable ();
            $table->text ('team_1_votes')->nullable ();
            $table->text ('team_2_votes')->nullable ();
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
        Schema::dropIfExists('pks');
    }
}
