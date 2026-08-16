<?php

namespace App\Http\Controllers;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Models\Coin;
use App\Models\User;
use App\Helpers\Common;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Google\Service\AndroidPublisher;
use App\Http\Controllers\Web\PaymentController;
use App\Models\CoinLog;
use App\Traits\User\PaymentTrait;
use Google_Client;
use Imdhemy\GooglePlay\ClientFactory;
use Imdhemy\GooglePlay\Products\ProductPurchase;
use Imdhemy\Purchases\Product;
use Tests\Products\ProductPurchaseTest;


class InAppPurchase extends Controller
{
    use PaymentTrait;

    protected $client;
     public function __construct()
     {
         $this->client = new \Google_Client();
//         $this->client = new Google_Client();
         $credentialPath = storage_path('app/credentials/google_payment.json');
         if (!file_exists($credentialPath)) {
             throw new \Exception('Google payment credential file does not exist at path: ' . $credentialPath);
         }
         $this->client->setApplicationName(config('app.name', 'App'));
         $this->client->setAuthConfig($credentialPath);
         $this->client->setScopes([AndroidPublisher::ANDROIDPUBLISHER]);

// //        $this->client->setScopes(['https://www.googleapis.com/auth/androidpublisher']);
     }


     public function googlePay($token, $productId, )
     {
         $client = ClientFactory::create([ClientFactory::SCOPE_ANDROID_PUBLISHER]);
         $product = new Product();
         try {
             // BUGFIX + white-label: was hardcoded to a foreign package name
             // Read the per-app package name from DB settings -> config('liap...').
             $packageName = Common::whiteLabel('google_play_package_name', 'liap.google_play_package_name');
             $response = $product->googlePlay($client)->packageName($packageName)->token($token)->id($productId)->get();
             //dd($response, 'this');
         } catch (GuzzleException $e) {
             //dd($e->getMessage(), 'this');
         }

                //  $subscription->verifyReceipt($client);
     }


    function xorDecrypt($input, $key)
    {
        $keyBytes = utf8_encode($key);
        $inputBytes = base64_decode($input);
        $decryptedData = '';

        for ($i = 0; $i < strlen($inputBytes); $i++) {
            $decryptedData .= chr(ord($inputBytes[$i]) ^ ord($keyBytes[$i % strlen($keyBytes)]));
        }

        return $decryptedData;
    }

    public function verifyToken($message, $key)
    {
        $decryptedData = $this->xorDecrypt($message, $key);

        if (json_decode($decryptedData, true)) {
            $data = json_decode($decryptedData, true);
            $merchantId = $data['merchantInfo']['merchantId'];
            $merchantName = $data['merchantInfo']['merchantName'];

            $env_merchantId = config('view.merchant_id');
            $env_merchantName = config('view.merchant_name');

            if ($env_merchantId !== $merchantId  ||  $env_merchantName !== $merchantName) {
                return [1, $data];
            }

            return [2, $data];
            //return successful message
        } else {
            return [0, null];
        }
    }


    public function tokenEncode($message, $key)
    {
        $encryptedData = $this->xorDecrypt($message, $key);
        $jsonData = json_encode($encryptedData);

        if ($jsonData !== false) {
            $data = base64_encode(serialize($jsonData));
            $data = unserialize(base64_decode($data), ['allowed_classes' => false]);
            $merchantId = $data['merchantInfo']['merchantId'];
            $merchantName = $data['merchantInfo']['merchantName'];

            $env_merchantId = config('view.merchant_id');
            $env_merchantName = config('view.merchant_name');

            if ($env_merchantId !== $merchantId || $env_merchantName !== $merchantName) {
                return [1, $data];
            }

            return [2, $data];
            //return successful message
        } else {
            return [0, null];
        }
    }

    public function pay(Request $request)
    {
        $path = storage_path('app/credentials/google_payment.json');
        putenv(sprintf("GOOGLE_APPLICATION_CREDENTIALS=%s", $path));
        $user = $request->user();

        $message = $request->message;
        $userId      = $user->id;
        $key     = $userId .'-'.config('view.decrypt_key');
        $type    = $request->type ?? 'google_pay';

        [$value, $data] = $this->verifyToken($message, $key);

        switch ($value) {
            case 0:
                return response()->json([
                    'status' => 404,
                    'message' => 'something went wrong',
                ],404);
            case 1:
                return Common::apiResponse(0, 'fail', null, 400);
            case 2:

                $trx = $data['processInfo']['token'];

                if (!$trx) return Common::apiResponse(false, __('Not Allowed'));

                $coins = Coin::where('id', $data['processInfo']['item_id'])->first();
                if (!$coins) return Common::apiResponse(false, __('item id not founded'));

                // Claim + credit through the locked reference standard: the coin_log
                // trx is the unique idempotency claim, so two concurrent deliveries
                // of the same purchase token collide on insert (1062) and only one
                // credits. Replaces the old check-then-credit that could double-mint.
                $result = $this->makePayment($trx, $coins->id, $userId, $type);
                if (!$result) return Common::apiResponse(false, __('Not Allowed'));

                return Common::apiResponse(true, 'success', null, 200);
            default:
                return Common::apiResponse(0, 'un handling exception', null, 400);
        }
    }

    public function verifyGooglePayPayment($productId, $purchaseToken)
    {
        $service = new AndroidPublisher($this->client);


//        try {
            // BUGFIX + white-label: was hardcoded to a foreign package name
            // Read the per-app package name from DB settings -> config('liap...').
            $response = $service->purchases_products->get(
                Common::whiteLabel('google_play_package_name', 'liap.google_play_package_name'),
                $productId,
                $purchaseToken
            );


            // Verify the purchase details
            if ($response->getPurchaseState() == 0) {
                // Purchase is valid
                // Acknowledge the purchase if necessary
                // Grant the product to the user in your system
                return response()->json();
            } else {
                // Handle invalid purchase
            }



        /*} catch (\Google\Service\Exception $e) {
            // Log or return the detailed error message for debugging
            error_log($e->getMessage());
            return false;
        }*/
    }

    public function verifyGooglePay(Request $request)
    {
        $productId = $request->input('productId') ?? 1;
        $purchaseToken = $request->input('purchaseToken') ?? 'this';


        $googlePlayVerifier = $this;
        $isVerified = $googlePlayVerifier->verifyGooglePayPayment($productId, $purchaseToken);

        if ($isVerified) {
            return response()->json(['status' => 'success', 'message' => 'Payment verified.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid payment.']);
        }
    }

    public function googleCoinsPay(Request $request)
    {
        return Common::apiResponse(false, 'حاول مره اخري', null, 422);
        $user = $request->user();
        $coins = Coin::find($request->coin_id);
        if(!$coins)return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        if(!$request->order_id)return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        
        $amountBefore =  $user->di;

        UserCoinLogHelper::logByType(
            $user->id,
            $coins?->coin,
            $amountBefore,
            UserCoinLogType::GOOGLE_PAY,
        );
        
        $user->di += $coins->coin;
        $user->save();
        $data=CoinLog::create([
            "obtained_coins"=> $coins->coin,
            "user_id"=>$user->id,
            'method'=>"google_pay",
            'donor_id'=>0,
            'donor_type'=>0,
            'status'=>1,
            'trx'=>$request->order_id,
        ]);
        return Common::apiResponse(1, 'تم الاضافه بنجاح', $data, 200);
    }
}
