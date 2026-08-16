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
        Schema::table('countries', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('e_name')->nullable()->change();
            $table->string('flag')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->string('phone_code')->nullable()->change();
            $table->string('language')->nullable()->change();
            $table->string('iso')->nullable()->change();
            $table->string('iso3')->nullable()->change();
            $table->string('continent_name')->nullable()->change();
            $table->string('e_continent_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {

        });
    }
};
