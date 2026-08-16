<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Schema::table('whatsapp_apps', function (Blueprint $table) {
        //     $table->integer('config')->default(0);
        //     $table->string('type')->default('whatsapp');
        // });
    }

    public function down(): void
    {
        // Schema::table('whatsapp_apps', function (Blueprint $table) {
        //     $table->dropColumn('config');
        // });
    }
};
