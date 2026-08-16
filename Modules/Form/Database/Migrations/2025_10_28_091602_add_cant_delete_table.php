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
        Schema::table('form_templates', function (Blueprint $table) {
            $table->boolean('can_not_delete')->default(false);
        });
        Schema::table('form_sections', function (Blueprint $table) {
            $table->boolean('can_not_delete')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('can_not_delete');
        });
        Schema::table('form_sections', function (Blueprint $table) {
            $table->dropColumn('can_not_delete');
        });
    }
};
