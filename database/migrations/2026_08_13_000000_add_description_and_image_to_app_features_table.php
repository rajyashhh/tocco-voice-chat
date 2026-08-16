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
        Schema::table('app_features', function (Blueprint $table) {
            if (!Schema::hasColumn('app_features', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('app_features', 'description_ar')) {
                $table->text('description_ar')->nullable();
            }
            if (!Schema::hasColumn('app_features', 'image')) {
                $table->string('image')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_features', function (Blueprint $table) {
            foreach (['description', 'description_ar', 'image'] as $column) {
                if (Schema::hasColumn('app_features', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
