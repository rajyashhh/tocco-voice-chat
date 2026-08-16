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
        if (!Schema::hasTable('user_coin_logs')) {
            Schema::create('user_coin_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();   
                $table->string('type')->nullable();                    
                $table->string('sub_type');                    
                $table->bigInteger('amount');                    
                $table->date('from_date')->nullable();                    
                $table->date('to_date')->nullable();                        
                $table->timestamps();
            });
        }
    }


    public function down()
    {
        Schema::dropIfExists('user_coin_logs');
    }
};
