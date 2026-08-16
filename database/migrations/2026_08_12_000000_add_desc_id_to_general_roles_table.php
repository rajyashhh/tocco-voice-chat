<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_roles', function (Blueprint $table) {
            $table->text('desc_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('general_roles', function (Blueprint $table) {
            $table->dropColumn('desc_id');
        });
    }
};
