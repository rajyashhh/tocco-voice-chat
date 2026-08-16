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
     
            Schema::create('coin_game_users_archive', function (Blueprint $table) {
                $table->unsignedBigInteger('id');
                $table->unsignedBigInteger('user_id');
                $table->bigInteger('coins')->nullable();
                $table->string('type', 50)->nullable();
                $table->unsignedBigInteger('game_id')->nullable();
                $table->unsignedBigInteger('round_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->bigInteger('app_profit_coins')->default(0);
                $table->timestamps();
    
                // عمود ضروري للPartition
                $table->unsignedInteger('created_ym');
    
                // PRIMARY KEY مع created_ym
                $table->primary(['id', 'created_ym']);
    
                $table->index(['user_id', 'game_id']);
                $table->index('created_at');
            });
    
            // Partition أولي للشهر الحالي + pMax
            $currentYm = (int) date('Ym');
            $nextYm = $currentYm + 1;
    
            DB::statement("
                ALTER TABLE coin_game_users_archive
                PARTITION BY RANGE (created_ym) (
                    PARTITION p{$currentYm} VALUES LESS THAN ({$nextYm}),
                    PARTITION pMax VALUES LESS THAN MAXVALUE
                )
            ");
        }
    
        public function down(): void
        {
            Schema::dropIfExists('coin_game_users_archive');
        }
};
