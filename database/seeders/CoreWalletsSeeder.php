<?php

namespace Database\Seeders;

use App\Models\CoreWallets;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoreWalletsSeeder extends Seeder
{
 
    public function run(): void
    {
        $walletNames = [
            'app_wallet',
            'owner_wallet',
            'game_wallet',
            'lucky_box',
            'agency',
            'host_agency',
            'lucky_gifts',
            'chinese_games',
            'games',
            'shipping_agents',
            'payment_gateways',
            'mall',
            'vip',
            'ads',
            'invitation_code_wallet',
            'room_cup_target'
        ];
        
        $negativeWallets = [
            'invitation_code_wallet',
            'room_cup_target',
        ];
        
        
        foreach ($walletNames as $name) {
            CoreWallets::updateOrCreate(
                ['name' => $name], 
                [
                    'is_negative' =>in_array($name,$negativeWallets)
                ]
            );
        }
    }
}
