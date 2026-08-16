<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

   class BackupWalletTransactions extends Command
{
    protected $signature = 'wallet:backup';
    protected $description = 'Wallet Backup ';

    public function handle()
    {
        $cutoff = now()->subMonth();
        $oldTransactions = WalletTransaction::where('created_at', '<', $cutoff)->get();

        foreach ($oldTransactions as $tx) {
            WalletTransactionBackup::create([
                'user_id' => $tx->user_id,
                'type' => $tx->type,
                'value' => $tx->value,
                'description' => $tx->description,
                'description_data' => $tx->description_data,
                'original_created_at' => $tx->created_at,
            ]);

            $tx->delete();
        }

//        $this->info("تم أرشفة {$oldTransactions->count()} معاملة.");
    }


}
