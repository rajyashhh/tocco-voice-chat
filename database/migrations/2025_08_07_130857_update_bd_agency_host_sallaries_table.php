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
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->decimal('salary', 12, 4)->nullable()->after('bd_user_id');

            $table->dropColumn(['user_sallary', 'agency_sallary']);
        });
    }

    public function down()
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->decimal('user_sallary', 12, 4)->nullable();
            $table->decimal('agency_sallary', 12, 4)->nullable();

            $table->dropColumn('salary');
        });
    }
};
