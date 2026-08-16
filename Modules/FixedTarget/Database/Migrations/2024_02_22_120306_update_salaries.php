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
         Schema::table('user_target', function (Blueprint $table) {
             $table->json('extras')->nullable()->change();

         });

         Schema::table('user_sallaries', function (Blueprint $table) {
             $table->json('extras')->nullable()->change();
         });
     }

     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::table('user_target', function (Blueprint $table) {
             $table->json('extras')->change();
         });

         Schema::table('user_sallaries', function (Blueprint $table) {
             $table->json('extras')->change();
         });
     }
};
