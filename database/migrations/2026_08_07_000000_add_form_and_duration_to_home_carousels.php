<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HomeCarouselController writes `form` (display-duration unit) and the
 * observer reads `duration`, but the 2023_11_15/16 migrations that were
 * supposed to add both columns have fully commented-out bodies — so any
 * fresh install lacks them and every banner INSERT fails with
 * "Unknown column 'form'". Idempotent: adds only what is missing.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            if (!Schema::hasColumn('home_carousels', 'form')) {
                $table->integer('form')->nullable();
            }
            if (!Schema::hasColumn('home_carousels', 'duration')) {
                $table->unsignedBigInteger('duration')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            if (Schema::hasColumn('home_carousels', 'form')) {
                $table->dropColumn('form');
            }
            if (Schema::hasColumn('home_carousels', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};