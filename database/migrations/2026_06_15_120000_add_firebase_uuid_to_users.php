<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirebaseUuidToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('users', 'firebase_uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('firebase_uuid')->nullable()->index();
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
        if (Schema::hasColumn('users', 'firebase_uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('firebase_uuid');
            });
        }
    }
}
