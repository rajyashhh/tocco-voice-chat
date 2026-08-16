<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ranking_ranges')) {
            Schema::create('ranking_ranges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ranking_type_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->smallInteger('min');
                $table->smallInteger('max')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('ranking_ranges');
    }
}
