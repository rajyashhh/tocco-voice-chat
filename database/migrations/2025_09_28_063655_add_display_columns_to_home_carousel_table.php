<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->boolean('display_discover')->default(false);
            $table->boolean('display_home_top')->default(false);
            $table->boolean('display_home_middle')->default(false);
            $table->boolean('display_live')->default(false);
            $table->boolean('display_country')->default(false);

        });
    }

    public function down()
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->dropColumn([
                'display_discover',
                'display_home_top',
                'display_home_middle',
                'display_live',
                'display_country',
            ]);
        });
    }
};
