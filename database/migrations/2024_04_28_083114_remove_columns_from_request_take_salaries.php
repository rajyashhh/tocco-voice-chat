<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_take_salaries', function (Blueprint $table) {
            $table->dropColumn('other');
            $table->dropColumn("bank_num");
            $table->dropColumn("name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_take_salaries', function (Blueprint $table) {
            $table->text('other')->nullable();
            $table->text("bank_num")->nullable();
            $table->text("name")->nullable();
        });
    }
};
