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
        Schema::table('official_messages', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->string('language')->nullable();
            $table->string('feature')->nullable();
            $table->string('sub_feature')->nullable();
            $table->string('feature_ids')->nullable();
            $table->string('member_title')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('official_messages', function (Blueprint $table) {
            $table->dropColumn('language');
            $table->dropColumn('feature');
            $table->dropColumn('sub_feature');
            $table->dropColumn('feature_ids');
            $table->dropColumn('member_title');
        });
    }
};
