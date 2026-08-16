<?php

namespace App\Http\Controllers;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use App\Models\CoinLog;
use App\Models\Coin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\Config;
use Illuminate\Http\Request;

class HuaweiPayController
{
    private $clientSecret;
    private $clientId;
    private $tokenUrl;
    private $accessToken;

    public function __construct()
    {
        $this->clientSecret = env('HUAWEI_PAY_CLIENT_SECRET', '');
        $this->clientId = env('HUAWEI_PAY_CLIENT_ID', '');
        //        $apiUrl = 'https://orders-dra.iap.cloud.huawei.asia/applications/purchases/tokens/verify';
        $this->tokenUrl = 'https://oauth-login.cloud.huawei.com/oauth2/v3/token';
        $this->accessToken = null;
    }

    public function index(Request $request)
    {
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'];
        $bodyDict = ['grant_type' => 'client_credentials', 'client_secret' => $this->clientSecret, 'client_id' => $this->clientId];
        $response = $this->httpPost($this->tokenUrl, $bodyDict, $headers);


        $array = json_decode($response['response']);


        $accessToken = $array->access_token;

        $oriString = "APPAT:$accessToken";
        $accessToken = "Basic " . base64_encode($oriString);
        $apiUrl = 'https://orders-dra.iap.cloud.huawei.asia/applications/purchases/tokens/verify';

        $clientId = env('HUAWEI_PAY_CLIENT_ID', ''); // set in .env
        $clientSecret = env('HUAWEI_PAY_CLIENT_SECRET', ''); // set in .env

        $credentials = $clientId . ':' . $clientSecret;
        $base64Credentials = base64_encode($credentials);
        $authorizationHeader = 'Basic ' . $base64Credentials;
        $client = new Client();

        $response = $client->post($apiUrl, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Authorization' => $accessToken,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'purchaseToken' => $request->token,
                'productId' => $request->productId,
            ],
        ]);
        // You can now handle the API response, for example:
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        $response = json_decode($body, true);
        if ($response && isset($response['purchaseTokenData'])) {

       

            $paymentStatus=$response['responseCode'];
            $purchaseTokenData = json_decode($response['purchaseTokenData'], true);
            $orderId = $purchaseTokenData['orderId'];
            $check_before=CoinLog::where("trx",$orderId)->first();
            $user_id= Auth::id();
            $user=User::find($user_id);
            $coins=Coin::find(request("productId"));
            if ($paymentStatus == 0 && $check_before== null) {
                // add to user di

                $amountBefore =  $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $coins?->coin,
                    $amountBefore,
                    UserCoinLogType::HUAWEI_PAY,
                );

                $user->di +=$coins?->coin;
                $user->save();
                $paid_usd = UserCommon::specialTransfer($coins?->coin);
                // add in coin log history
                $data=CoinLog::create([
                    "paid_usd"=>$paid_usd ,
                    "obtained_coins"=>$coins?->coin,
                    "user_id"=>$user_id,
                    'method'=>"huawei_pay",
                    'donor_id'=>0,
                    'donor_type'=>0,
                    'status'=>1,
                    'trx'=>$orderId,
                ]);

           
                return Common::apiResponse(1, 'تم الاضافه بنجاح', $data, 200);
            }else{
                return Common::apiResponse(0, 'تمت العمليه من قبل!', 200);
            }
        }else{
            return Common::apiResponse(0, 'هناك مشكله حاول مره اخرى!', 200);
        }
    }

    public function getAppAT()
    {
        if ($this->accessToken !== null && $this->accessToken !== "") {
            return $this->accessToken;
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'];
        $bodyDict = ['grant_type' => 'client_credentials', 'client_secret' => $this->clientSecret, 'client_id' => $this->clientId];
        $response = $this->httpPost($this->tokenUrl, $bodyDict, $headers);

        if ($response !== null && $response !== "") {
            $jsonObject = json_decode($response, true);
            $this->accessToken = $jsonObject['access_token'];

            // TODO: Show the access token in the console. You can remove this line.
            echo $this->accessToken;
        }

        return $this->accessToken;
    }

    public function httpPost($url, $params, $headers)
    {
        $client = new Client();

        try {
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $params,
                'timeout' => 50,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            return ['response' => $responseBody, 'statusCode' => $statusCode];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            echo 'Request error: ' . $e->getMessage();

            return ['response' => null, 'statusCode' => $statusCode];
        } catch (Exception $e) {
            echo $e->getMessage();

            return ['response' => null, 'statusCode' => null];
        }
    }

    public function buildAuthorization($appAt)
    {
        $oriString = "APPAT:$appAt";
        $authorization = "Basic " . base64_encode($oriString);
        $headers = ["Authorization" => $authorization, "Content-Type" => "application/json; charset=UTF-8"];

        return $headers;
    }
}
