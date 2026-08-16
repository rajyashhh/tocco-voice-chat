<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowImageToBadgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('badges', 'show_image')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->string('show_image')->nullable()->after('image');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('badges', 'show_image')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->dropColumn('show_image');
            });
        }
    }
}
