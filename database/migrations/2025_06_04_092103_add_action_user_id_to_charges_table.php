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
            $table->bigInteger('action_user_id')->nullable()->after('id');

        });
    }

    public function down()
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn('action_user_id');
        });
    }
};
