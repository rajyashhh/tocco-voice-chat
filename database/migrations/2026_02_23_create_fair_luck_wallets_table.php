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
        Schema::create('fair_luck_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_type')->unique(); // global_vault, jackpot_wallet, medium_wallet
            $table->bigInteger('balance')->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
            
            $table->index('wallet_type');
        });

        // إدراج البيانات الافتراضية للمحافظ الثلاث
        DB::table('fair_luck_wallets')->insert([
            [
                'wallet_type' => 'global_vault',
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'wallet_type' => 'jackpot_wallet', 
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'wallet_type' => 'medium_wallet',
                'balance' => 0,
                'created_at' => now(), 
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fair_luck_wallets');
    }
};