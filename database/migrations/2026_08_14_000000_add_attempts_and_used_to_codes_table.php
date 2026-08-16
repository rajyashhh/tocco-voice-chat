<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttemptsAndUsedToCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('codes', function (Blueprint $table) {
            if (!Schema::hasColumn('codes', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0)->after('code');
            }
            if (!Schema::hasColumn('codes', 'used')) {
                $table->boolean('used')->default(false)->after('attempts');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('codes', function (Blueprint $table) {
            if (Schema::hasColumn('codes', 'used')) {
                $table->dropColumn('used');
            }
            if (Schema::hasColumn('codes', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
}
