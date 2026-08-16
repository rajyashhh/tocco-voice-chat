<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_packs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agency_id');
            $table->bigInteger('sender_id');
            $table->string('sender_type');
            $table->bigInteger('vip_user_id');
            $table->bigInteger('dash_user_id');
            $table->integer('target_id')->unsigned();
            $table->tinyInteger('get_type')->unsigned();
            $table->tinyInteger('type')->unsigned();
            $table->integer('num')->unsigned()->default(1);
            $table->integer('expire');
            $table->tinyInteger('is_read')->default(0);
            $table->tinyInteger('is_used')->default(0);
            $table->integer('use_num')->default(0);
            $table->integer('price')->default(0);
            $table->integer('price_item')->default(0);
            $table->boolean('using')->default(0);
            $table->integer('days');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_packs');
    }
};
