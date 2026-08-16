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
        Schema::table('packs', function (Blueprint $table) {
            $table->string('receive_type')->nullable();
        });

        Schema::table('users_vips', function (Blueprint $table) {
            $table->string('receive_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropColumn('receive_type');
        });

        Schema::table('users_vips', function (Blueprint $table) {
            $table->dropColumn('receive_type');
        });
    }
};
