<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\FixedTarget\Enums\TargetType;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::table('user_target', function (Blueprint $table) {
//            $table->string('type')->default(TargetType::REGULAR->value)->comment('to show all types go to Modules\FixedTarget\Enums\TargetType');
//            $table->json('extras')->nullable();
//
//        });
//
//        Schema::table('user_sallaries', function (Blueprint $table) {
//            $table->string('type')->default(TargetType::REGULAR->value)->comment('to show all types go to Modules\FixedTarget\Enums\TargetType');
//            $table->boolean('is_saved')->default(false);
//            $table->json('extras')->nullable();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('user_target', function (Blueprint $table) {
//            $table->dropColumn('type');
//            $table->dropColumn('extras');
//        });
//
//        Schema::table('user_sallaries', function (Blueprint $table) {
//            $table->dropColumn('type');
//            $table->dropColumn('is_saved');
//            $table->dropColumn('extras');
//
//        });
    }
};
