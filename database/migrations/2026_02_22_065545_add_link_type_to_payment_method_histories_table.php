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
        if (!Schema::hasColumn('payment_method_histories', 'link_type')) {
            Schema::table('payment_method_histories', function (Blueprint $table) {
                $table->string('link_type')->nullable()->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_method_histories', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });
    }
};
