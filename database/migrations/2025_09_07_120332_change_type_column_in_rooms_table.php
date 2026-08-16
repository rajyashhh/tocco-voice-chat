<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('type', 20)
                  ->collation('utf8mb4_unicode_ci')
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('type', ['audio', 'single_live', 'multi_live'])
                  ->collation('utf8mb4_unicode_ci')
                  ->change();
        });
    }
};
