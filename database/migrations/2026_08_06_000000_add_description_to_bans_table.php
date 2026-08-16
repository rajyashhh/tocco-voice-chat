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
        Schema::table('bans', function (Blueprint $table) {
            if (!Schema::hasColumn('bans', 'description_ar')) {
                $table->text('description_ar')->nullable();
            }
            if (!Schema::hasColumn('bans', 'description_en')) {
                $table->text('description_en')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bans', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['description_ar', 'description_en'],
                fn ($column) => Schema::hasColumn('bans', $column)
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
