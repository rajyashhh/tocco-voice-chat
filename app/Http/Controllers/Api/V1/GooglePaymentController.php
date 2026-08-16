<?php

namespace App\Http\Controllers\Api\V1;

use Modules\Vip\Entities\Vip;
use App\Models\Coin;
use App\Models\User;
use GuzzleHttp\Client;
use App\Helpers\Common;
use App\Models\CoinLog;
use Illuminate\Http\Request;
use App\Traits\User\PaymentTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
// use Google_Client;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\GuzzleException;

class GooglePaymentController extends Controller
{

    use PaymentTrait;
    public function purchased(Request $request)
    {
        // White-label: per-app SA file + app name from config, NEVER a foreign
        // literal. Defaults to the generic google_payment.json credential file.
        $serviceAccountFile = storage_path('app/credentials/' . env('GOOGLE_PAYMENT_SA_FILE', 'google_payment.json'));
        // Set the scopes
        $scopes = ['https://www.googleapis.com/auth/sqlservice.admin'];

        // Create a new Google API client
        $client =  new \Google_Client();
        $client->setApplicationName(config('app.name', 'App'));
        $client->setAuthConfig($serviceAccountFile);
        $client->addScope($scopes);

        // Fetch credentials
        $credentials = $client->fetchAccessTokenWithAssertion();

        // dd($credentials);
        // Check if the access token is present
        if (isset($credentials['access_token'])) {
            $accessToken = "Bearer " . $credentials['access_token'];
            // first json_encode the access token before sending it to $client->setAccessToken();
            $json_encoded_access_token = json_encode([
                'access_token' => $accessToken,
                'created' => $credentials['created'],  // make up values for these.. otherwise the client thinks the token has expired..
                'expires_in' => $credentials['expires_in'] // made up a value in the future...
            ]);

            // and then set it
            // $client->setAccessToken($json_encoded_access_token);

            // Set up a new Google API client for the Android Publisher service
            $androidClient =  new \Google_Client();
            $androidClient->setApplicationName("YourAppName");
            $androidClient->setAuthConfig($serviceAccountFile);
            $androidClient->setAccessToken($credentials);

            // Check if the access token is expired and refresh it if necessary
            if ($androidClient->isAccessTokenExpired()) {
                $androidClient->fetchAccessTokenWithRefreshToken($androidClient->getRefreshToken());
            }

            // Set up the Android Publisher service
            // BUGFIX + white-label: the package was hardcoded to a FOREIGN app
            // mismatched the per-app package name -> IAP verification
            // hit the wrong package. Read per-app: DB setting -> config('liap...')
            // (env GOOGLE_PLAY_PACKAGE_NAME). Empty for a fresh clone.
            $packageName = Common::whiteLabel('google_play_package_name', 'liap.google_play_package_name');
            $androidService = new \Google_Service_AndroidPublisher($androidClient);

            // This is a simplified example. Ensure proper error handling and security in your implementation.
            $response = $androidService->purchases_products->get($packageName, $request->productId, $request->purchaseToken);

            return $response;
        } else {
            // Handle the case when the access token is not present
            return response()->json(['error' => 'Access token not found'], 401);
        }
    }


    public function purchasedFour(Request $request)
    {
        $client = new \GuzzleHttp\Client();
        $url = config('app.payment_url') . '/api/google-pay';

        try {


            $productId = $request->productId;
            $response  = $client->post($url, [
                'json' => [
                    'purchaseToken' => $request->purchaseToken,
                    'productId'     => $productId,
                    'serverKey'     => config('app.node_server_name') // this required
                ],
            ]);
            $body = $response->getBody()->getContents();

            $data = json_decode($body);

            $bodyData = @$data->data ?? null;
            if (@$data->valid && $bodyData) {
                $orderId = $bodyData->orderId;

                $userId = Auth::id();

                $data = $this->makePayment($orderId, $productId, $userId, type: "google_pay");

                if ($data === false) {
                    return Common::apiResponse(0, 'تمت العمليه من قبل!', 402);
                } else {
                    return Common::apiResponse(1, 'تم الاضافه بنجاح', $data, 200);
                }
            }
        } catch (GuzzleException $e) {
            return Common::apiResponse(0, 'هناك مشكله حاول مره اخرى!', 402);
        }
        return Common::apiResponse(0, 'هناك مشكله حاول مره اخرى!', 402);
    }


    public static function addChargeLevel(Request $request)
    {
        $user = User::where("id", $request->user()->id)->first();
        $user->total_charge_coins += $request->amount;
        $chargeUserExp = $user->total_charge_coins + $user->sub_charger_level;
        $level = Vip::where("exp", "<=",  $chargeUserExp)->where('type', 5)->orderByDesc("exp")->first();

        if ($level) {
            $user->charge_level = $level->level;
        }
        $user->save();

        return Common::apiResponse(1,  $user->total_charge_level, 200);
    }
}
