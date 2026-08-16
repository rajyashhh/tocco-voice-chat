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
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->decimal('amount_before', 16, 2)->default(0);
            $table->enum('action_type', ['add', 'subtract'])->default('add');
            $table->string('item_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->dropColumn([ 'action_type','amount_before','item_name']);
        });
    }
};
