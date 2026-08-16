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
        Schema::create('achievement_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->nullable()->constrained('achievements')->onDelete('cascade');
            $table->integer('target')->nullable();
            $table->string('target_type')->nullable();
            $table->string('valid_image')->nullable();
            $table->string('invalid_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_levels');
    }
};
