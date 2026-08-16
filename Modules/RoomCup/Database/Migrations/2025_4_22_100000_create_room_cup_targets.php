<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('room_cup_targets', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('total')->default(0);
        //     $table->bigInteger('number_of_visitors')->default(0);
        //     $table->bigInteger('number_of_admins')->default(0);
        //     $table->decimal('owner_profit', 8, 2)->default(0); 
        //     $table->decimal('admin_profit', 8, 2)->default(0); 
        //     $table->timestamps();

        //     $table->index('number_of_visitors');
        //     $table->index('number_of_admins');
        //     $table->index('owner_profit');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('room_cup_targets');
    }
};
