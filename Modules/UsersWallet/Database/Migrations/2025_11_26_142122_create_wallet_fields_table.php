<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_template_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->string('type', 50);
            $table->boolean('is_required')->default(1);
            $table->integer('order')->default(0);
            $table->json('placeholder')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_fields');
    }
}
