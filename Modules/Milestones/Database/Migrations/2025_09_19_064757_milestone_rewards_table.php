<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('milestone_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('rewardable_id');
            $table->string('rewardable_type'); 
            $table->integer('reward')->default(1); 
            $table->string('type')->nullable(); 
            $table->integer('expire')->nullable();
            $table->timestamps();
        });



    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_rewards');
    }
};
