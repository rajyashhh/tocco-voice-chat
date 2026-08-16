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
        Schema::table('bd_salaries', function (Blueprint $table) {
            $table->decimal('salary', 20, 4)->change();
            $table->decimal('cut_amount', 20, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('bd_salaries', function (Blueprint $table) {
            $table->decimal('salary', 12, 2)->change();
            $table->decimal('cut_amount', 12, 2)->change();
        });
    }
};
