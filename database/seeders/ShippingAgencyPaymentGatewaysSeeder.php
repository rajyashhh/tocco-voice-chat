<?php

namespace Database\Seeders;

use App\Models\PaymentCoin;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Storage;

class ShippingAgencyPaymentGatewaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $images = [
            'fawry'     => 'payments/fawry.png',
            'paysky'    => 'payments/paysky.png',
            'stripe'    => 'payments/stripe.png',
            'opay'      => 'payments/opay.png',
            'cashfree'  => 'payments/cashfree.png',
            'applepay'  => 'payments/applepay.png',
            'paypal'    => 'payments/paypal.png',
            'paytabs'   => 'payments/paytabs.png',
            'googlepay' => 'payments/googlepay.png',
        ];

        foreach ($images as $img) {
            $localPath = public_path("images/{$img}");
            $gcsPath   = "images/{$img}";
            if (file_exists($localPath)) {
                Storage::disk('gcs')->put($gcsPath, file_get_contents($localPath), 'public');
            }
        }

        $gateways = [
            'fawry' => [
                'photo' => $images['fawry'],
                'fields' => [
                    'fawry_secret_agency' => ['type' => 'input', 'value' => ''],
                    'fawry_merchant_code_agency' => ['type' => 'input', 'value' => ''],
                    'fawry_url_agency' => ['type' => 'input', 'value' => 'https://atfawry.com/fawrypay-api/api/payments/init'],
                    'fawry_return_url_agency' => ['type' => 'input', 'value' => url('/api/fawry-callback-agency')],
                ],
            ],
            'paysky' => [
                'photo' => $images['paysky'],
                'fields' => [
                    'paysky_profile_id_agency' => ['type' => 'input', 'value' => ''],
                    'paysky_secret_key_agency' => ['type' => 'input', 'value' => ''],
                    'paysky_url_agency' => ['type' => 'input', 'value' => 'https://intg-pg.paysky.io/'],
                ],
            ],
            'stripe' => [
                'photo' => $images['stripe'],
                'fields' => [
                    'stripe_secret_key_agency' => ['type' => 'input', 'value' => 'sk_test_xxx'],
                    'stripe_success_url_agency' => ['type' => 'input', 'value' => url('/api/stripe-callback-agency/success')],
                    'stripe_cancel_url_agency' => ['type' => 'input', 'value' => url('/api/stripe-callback-agency/cancel')],
                ],
            ],
            'opay' => [
                'photo' => $images['opay'],
                'fields' => [
                    'opay_public_key_agency' => ['type' => 'input', 'value' => ''],
                    'opay_secret_key_agency' => ['type' => 'input', 'value' => ''],
                    'opay_merchant_id_agency' => ['type' => 'input', 'value' => ''],
                ],
            ],
            'cashfree' => [
                'photo' => $images['cashfree'],
                'fields' => [
                    'cashfree_app_id_agency' => ['type' => 'input', 'value' => ''],
                    'cashfree_secret_key_agency' => ['type' => 'input', 'value' => ''],
                    'cashfree_mode_agency' => ['type' => 'select', 'value' => 'sandbox'],
                ],
            ],
            'applepay' => [
                'photo' => $images['applepay'],
                'fields' => [
                    'applepay_merchant_id_agency' => ['type' => 'input', 'value' => ''],
                    'applepay_key_id_agency' => ['type' => 'input', 'value' => ''],
                ],
            ],
            'paypal' => [
                'photo' => $images['paypal'],
                'fields' => [
                    'paypal_client_id_agency' => ['type' => 'input', 'value' => ''],
                    'paypal_secret_agency' => ['type' => 'input', 'value' => ''],
                    'paypal_mode_agency' => ['type' => 'select', 'value' => 'sandbox'],
                ],
            ],
            'paytabs' => [
                'photo' => $images['paytabs'],
                'fields' => [
                    'paytabs_profile_id_agency' => ['type' => 'input', 'value' => ''],
                    'paytabs_server_key_agency' => ['type' => 'input', 'value' => ''],
                    'paytabs_region_agency' => ['type' => 'input', 'value' => 'EGYPT'],
                ],
            ],
            'googlepay' => [
                'photo' => $images['googlepay'],
                'fields' => [
                    'googlepay_merchant_id_agency' => ['type' => 'input', 'value' => ''],
                    'googlepay_gateway_agency' => ['type' => 'input', 'value' => 'example'],
                ],
            ],
        ];

        foreach ($gateways as $type => $gateway) {
            $gatewayModel = PaymentCoin::updateOrCreate(
                ['title' => "{$type}_agency"],
                [
                    'photo' => "images/{$gateway['photo']}",
                    'status' => 1,
                    'type' => $type,
                    'package_type' => 'shipping_agency',
                    'description' => ucfirst($type) . ' payment method for agencies',

                ]
            );

            foreach ($gateway['fields'] as $key => $field) {
                // Setting::updateOrCreate(
                //     [
                //         'key' => $key,
                //         'item_id' => $gatewayModel->id,
                //         'type' => 'payment',
                //     ],
                //     [
                //         'value' => $field['value'],
                //         'input_type' => $field['type'],
                //     ]
                // );
            }
        }
    }
}
