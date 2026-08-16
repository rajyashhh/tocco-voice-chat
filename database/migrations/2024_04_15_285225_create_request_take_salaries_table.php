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
        Schema::create('request_take_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double("amount")->default(0);
            $table->text("phone")->nullable();
            $table->text("gmail")->nullable();
            $table->text("country")->nullable();
            $table->text("bank_num")->nullable();
            $table->text("name")->nullable();
            $table->integer("status")->comment("0=>pending,1=>acceptable,2=>refused")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_take_salaries');
    }
};
