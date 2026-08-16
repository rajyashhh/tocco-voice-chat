<?php

namespace Modules\UsersWallet\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UsersWallet\Entities\WalletField;
use Modules\UsersWallet\Entities\WalletTemplate;

class BankAccountTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $walletData = [
            'title' => [
                'ar' => 'بيانات بنكية',
                'en' => 'Bank Information',
                'tr' => 'Banka Bilgileri',
                'hi' => 'बैंक जानकारी',
            ],
            'type' => 'bank_account',
            'minimum' => 50.00,
            'transfer_fee' => 2.00,
        ];

        $template = WalletTemplate::updateOrCreate(
            ['title->en' => $walletData['title']['en']],
            $walletData
        );

        $fields = [
            [
                'title' => [
                    'ar' => 'اسم البنك',
                    'en' => 'Bank Name',
                    'tr' => 'Banka Adı',
                    'hi' => 'बैंक का नाम',
                ],
                'placeholder' => [
                    'ar' => 'مثال: البنك الأهلي المصري',
                    'en' => 'Example: National Bank of Egypt',
                    'tr' => 'Örnek: Mısır Ulusal Bankası',
                    'hi' => 'उदाहरण: नेशनल बैंक ऑफ इजिप्ट',
                ],
                'type' => 'text',
                'is_required' => 1,
                'order' => 1,
            ],
            [
                'title' => [
                    'ar' => 'رقم الحساب / IBAN',
                    'en' => 'Account Number / IBAN',
                    'tr' => 'Hesap Numarası / IBAN',
                    'hi' => 'खाता संख्या / आईबीएएन',
                ],
                'placeholder' => [
                    'ar' => 'أدخل رقم الحساب',
                    'en' => 'Enter account number',
                    'tr' => 'Hesap numarasını girin',
                    'hi' => 'खाता संख्या दर्ज करें',
                ],
                'type' => 'text',
                'is_required' => 1,
                'order' => 2,
            ],
            [
                'title' => [
                    'ar' => 'اسم صاحب الحساب',
                    'en' => 'Account Holder Name',
                    'tr' => 'Hesap Sahibinin Adı',
                    'hi' => 'खाता धारक का नाम',
                ],
                'placeholder' => [
                    'ar' => 'الاسم كما هو في البنك',
                    'en' => 'Name as per bank record',
                    'tr' => 'Bankadaki isimle aynı',
                    'hi' => 'बैंक रिकॉर्ड के अनुसार नाम',
                ],
                'type' => 'text',
                'is_required' => 1,
                'order' => 3,
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
