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
        Schema::table('request_background_images', function (Blueprint $table) {
            $table->double("price")->default(0)->change();
            $table->integer("expair")->default(0);
            $table->string("type")->default("user");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_background_images', function (Blueprint $table) {
            //
        });
    }
};
