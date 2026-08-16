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
        Schema::table('cp_relations', function (Blueprint $table) {
            $table->enum('type',[0,1,2,3])->default(0)->comment('الاخوة و صديق حميم و حبايب و حلال العلاقة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_relations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
