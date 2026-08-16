<?php

namespace Modules\UsersWallet\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UsersWallet\Entities\WalletField;
use Modules\UsersWallet\Entities\WalletTemplate;

class DigitalWalletTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $wallets = [
            [
                'title' => [
                    'ar' => 'فودافون كاش',
                    'en' => 'Vodafone Cash',
                    'tr' => 'Vodafone Cüzdan',
                    'hi' => 'वोडाफोन कैश',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
            ],
            [
                'title' => [
                    'ar' => 'اتصالات كاش',
                    'en' => 'Etisalat Cash',
                    'tr' => 'Etisalat Cüzdan',
                    'hi' => 'एतिसलात कैश',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
            ],
            [
                'title' => [
                    'ar' => 'اورنج كاش',
                    'en' => 'Orange Cash',
                    'tr' => 'Orange Cüzdan',
                    'hi' => 'ऑरेंज कैश',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
            ],
            [
                'title' => [
                    'ar' => 'وي باي',
                    'en' => 'WE Pay',
                    'tr' => 'WE Ödeme',
                    'hi' => 'डब्ल्यूई पे',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
            ],
        ];

        foreach ($wallets as $walletData) {
            $template = WalletTemplate::updateOrCreate(
                ['title->en' => $walletData['title']['en']],
                $walletData
            );

            $fields = [
                [
                    'title' => [
                        'ar' => 'رقم الهاتف',
                        'en' => 'Phone number',
                        'tr' => 'Telefon numarası',
                        'hi' => 'फ़ोन नंबर',
                    ],
                    'placeholder' => [
                        'ar' => '01XXXXXXXXXX',
                        'en' => '01XXXXXXXXXX',
                        'tr' => '01XXXXXXXXXX',
                        'hi' => '01XXXXXXXXXX',
                    ],
                    'type' => 'text',
                    'is_required' => 1,
                    'order' => 1,
                ],
            ];

            foreach ($fields as $field) {
                WalletField::updateOrCreate(
                    [
                        'wallet_template_id' => $template->id,
                        'title->en' => $field['title']['en'],
                    ],
                    array_merge($field, ['wallet_template_id' => $template->id])
                );
            }
        }
    }
}
