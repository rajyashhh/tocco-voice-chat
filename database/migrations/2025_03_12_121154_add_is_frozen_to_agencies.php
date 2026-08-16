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
        if (!Schema::hasColumn('agencies', 'is_frozen')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->boolean('is_frozen')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('agencies', 'is_frozen')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropColumn('is_frozen');
            });
        }
    }
};
