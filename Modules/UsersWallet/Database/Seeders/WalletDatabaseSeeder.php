<?php

namespace Modules\UsersWallet\Database\Seeders;

use Illuminate\Database\Seeder;

class WalletDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DigitalWalletTemplateSeeder::class,
            OtherWalletTemplateSeeder::class,
            BankAccountTemplateSeeder::class
        ]);
    }
}
