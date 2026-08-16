<?php

namespace Modules\UsersWallet\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\UsersWallet\Entities\WalletField;
use Modules\UsersWallet\Entities\WalletTemplate;

class OtherWalletTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $wallets = [
            [
                'title' => [
                    'ar' => 'انستا باي',
                    'en' => 'Instapay',
                    'tr' => 'Instapay',
                    'hi' => 'इंस्टापे',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
                'fields' => [
                    [
                        'title' => [
                            'ar' => 'معرف انستا باي',
                            'en' => 'Instapay ID',
                            'tr' => 'Instapay Kimliği',
                            'hi' => 'इंस्टापे आईडी',
                        ],
                        'placeholder' => [
                            'ar' => 'username@instapay',
                            'en' => 'username@instapay',
                            'tr' => 'username@instapay',
                            'hi' => 'username@instapay',
                        ],
                        'type' => 'text',
                        'is_required' => 1,
                        'order' => 1,
                    ],
                ],
            ],

            [
                'title' => [
                    'ar' => 'باينانس',
                    'en' => 'Binance',
                    'tr' => 'Binance',
                    'hi' => 'बाइनेंस',
                ],
                'type' => 'digital_wallet',
                'minimum' => 50.00,
                'transfer_fee' => 0.00,
                'fields' => [
                    [
                        'title' => [
                            'ar' => 'عنوان المحفظة',
                            'en' => 'Wallet Address',
                            'tr' => 'Cüzdan Adresi',
                            'hi' => 'वॉलेट पता',
                        ],
                        'placeholder' => [
                            'ar' => 'أدخل عنوان المحفظة',
                            'en' => 'Enter wallet address',
                            'tr' => 'Cüzdan adresini girin',
                            'hi' => 'वॉलेट पता दर्ज करें',
                        ],
                        'type' => 'text',
                        'is_required' => 1,
                        'order' => 1,
                    ],
                    [
                        'title' => [
                            'ar' => 'الشبكة',
                            'en' => 'Network',
                            'tr' => 'Ağ',
                            'hi' => 'नेटवर्क',
                        ],
                        'placeholder' => [
                            'ar' => 'مثال: BEP20 / ERC20',
                            'en' => 'Example: BEP20 / ERC20',
                            'tr' => 'Örnek: BEP20 / ERC20',
                            'hi' => 'उदाहरण: BEP20 / ERC20',
                        ],
                        'type' => 'text',
                        'is_required' => 1,
                        'order' => 2,
                    ],
                ],
            ],
        ];

        foreach ($wallets as $walletData) {
            $fields = $walletData['fields'];
            unset($walletData['fields']);

            $template = WalletTemplate::updateOrCreate(
                ['title->en' => $walletData['title']['en']],
                $walletData
            );

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
