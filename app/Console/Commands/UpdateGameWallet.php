<?php

namespace App\Console\Commands;

use App\Models\GameWallet;
use Illuminate\Console\Command;

class UpdateGameWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-game-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameWallet = GameWallet::latest()->first();
        if (!$gameWallet) {
            return ;
        }

        $remaining = $gameWallet->balance - $gameWallet->used;

        GameWallet::create([
            "balance" => $remaining,
            "used" => 0,
        ]);


//        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }
}
