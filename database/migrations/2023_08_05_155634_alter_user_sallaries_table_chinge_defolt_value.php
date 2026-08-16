<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserSallariesTableChingeDefoltValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            // Modify the existing column
            $table->decimal('owner_pide')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            // Revert the column change if needed
            $table->decimal('owner_pide')->default(0)->change();
        });
    }
}
