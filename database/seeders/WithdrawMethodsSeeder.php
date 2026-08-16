<?php

namespace Database\Seeders;

use App\Models\PaymentWithdrawField;
use App\Models\PaymentWithdrawType;
use Illuminate\Database\Seeder;

/**
 * Seeds the four base withdraw methods so a fresh install/clone starts with
 * usable payout options instead of zero (the old PaymentWithdrawType system had
 * no seeder at all). Consumed by the unified withdraw flow in
 * Modules/UsersWallet (WalletHelper::createWithdrawal + /wallets/withdraw-methods).
 *
 * CREATE-ONLY / idempotent: a method is inserted only when no row with the same
 * name_en exists, and its fields only when absent. Re-running never overwrites
 * amounts, exchange rates, or any method the owner tuned/added by hand.
 *
 * name = Arabic label, name_en = English label (matches the model + resources,
 * which pick per locale). validate holds the client-side hint the resource
 * exposes as `validate`.
 */
class WithdrawMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name'      => 'تحويل بنكي',
                'name_en'   => 'Bank Transfer',
                'min_value' => 50,
                'fields'    => [
                    ['name' => 'اسم البنك', 'name_en' => 'Bank Name', 'type' => 'string', 'validate' => 'required'],
                    ['name' => 'رقم الحساب / IBAN', 'name_en' => 'Account Number / IBAN', 'type' => 'string', 'validate' => 'required'],
                    ['name' => 'اسم صاحب الحساب', 'name_en' => 'Account Holder Name', 'type' => 'string', 'validate' => 'required'],
                ],
            ],
            [
                'name'      => 'USDT (كريبتو)',
                'name_en'   => 'USDT (Crypto)',
                'min_value' => 20,
                'fields'    => [
                    ['name' => 'الشبكة', 'name_en' => 'Network', 'type' => 'string', 'validate' => 'required'],
                    ['name' => 'عنوان المحفظة', 'name_en' => 'Wallet Address', 'type' => 'string', 'validate' => 'required'],
                ],
            ],
            [
                'name'      => 'باي بال',
                'name_en'   => 'PayPal',
                'min_value' => 20,
                'fields'    => [
                    ['name' => 'بريد باي بال', 'name_en' => 'PayPal Email', 'type' => 'string', 'validate' => 'required'],
                ],
            ],
            [
                'name'      => 'فودافون كاش',
                'name_en'   => 'Vodafone Cash',
                'min_value' => 10,
                'fields'    => [
                    ['name' => 'رقم المحفظة', 'name_en' => 'Wallet Number', 'type' => 'int', 'validate' => 'required'],
                ],
            ],
        ];

        foreach ($methods as $method) {
            $fields = $method['fields'];
            unset($method['fields']);

            $type = PaymentWithdrawType::where('name_en', $method['name_en'])->first();
            if (!$type) {
                $type = PaymentWithdrawType::create($method);
            }

            foreach ($fields as $field) {
                $exists = PaymentWithdrawField::where('payment_withdraw_type_id', $type->id)
                    ->where('name_en', $field['name_en'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                PaymentWithdrawField::create(array_merge($field, [
                    'payment_withdraw_type_id' => $type->id,
                ]));
            }
        }
    }
}