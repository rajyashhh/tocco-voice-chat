<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vips', function (Blueprint $table) {
            $table->id();
            $table->integer ('type')->default (0)->comment ('1 نجمة شارب 2 ذهبي حاد 3 كبار الشخصيات');
            $table->integer ('level')->default (0)->comment ('المستوى');
            $table->bigInteger ('exp')->default (0)->comment ('خبرة');
            $table->bigInteger ('di')->default (0)->comment ('ماسات');
            $table->bigInteger ('co')->default (0)->comment ('عملات');
            $table->string ('img')->nullable ();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vips');
    }
}
