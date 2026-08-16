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
        Schema::create('notification_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->string('language', 10);
            $table->string('title');
            $table->text('message');
            $table->unique(['notification_id', 'language']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_translations');
    }
};
