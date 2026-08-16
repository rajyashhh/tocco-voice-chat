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
        Schema::table('charges', function (Blueprint $table) {
            $table->string('user_charger_type')->nullable()->after('charger_type');
        });
    }

    public function down()
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn('user_charger_type');
        });
    }
};
