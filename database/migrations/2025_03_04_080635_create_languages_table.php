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
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->enum('direction', ['LTR', 'RTL'])->default('LTR');
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        public function down()
        {
            Schema::dropIfExists('languages');
        }
    };
