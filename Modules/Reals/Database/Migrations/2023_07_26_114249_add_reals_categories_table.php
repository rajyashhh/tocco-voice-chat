<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reals_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('real_id')->constrained('reals')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('video_categories')->onDelete('cascade');
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
        Schema::dropIfExists('reals_categoriesa');
    }
};
