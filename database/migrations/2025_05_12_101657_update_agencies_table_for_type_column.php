<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('agencies', function (Blueprint $table) {
            // $table->dropColumn('Host_agency');
            // $table->dropColumn('Shipping_agency');
            $table->integer('type')->after('id')->comment('1 = Host Agency, 2 = Shipping');
        });
    }

    public function down()
    {
        Schema::table('agencies', function (Blueprint $table) {
            // $table->boolean('Host_agency')->default(false);
            // $table->boolean('Shipping_agency')->default(false);
            $table->dropColumn('type');
        });
    }
};
