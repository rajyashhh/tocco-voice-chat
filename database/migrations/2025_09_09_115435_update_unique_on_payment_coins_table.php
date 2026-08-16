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
        Schema::table('payment_coins', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('payment_coins');

            if (array_key_exists('payment_coins_type_unique', $indexes)) {
                $table->dropUnique('payment_coins_type_unique');
            } elseif (array_key_exists('type_unique', $indexes)) {
                $table->dropUnique('type_unique');
            }
            
            $table->unique(['type', 'package_type'], 'type_package_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_coins', function (Blueprint $table) {
            $table->dropUnique('type_package_unique');
            $table->unique('type');
        });
    }
};
