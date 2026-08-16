<?php

namespace Modules\Payment\Http\Controllers;

use App\Helpers\Common;
use App\Models\Coin;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Entities\UserCoinPayment;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Http\Requests\CreateRequest;
//use Nafezly\Payments\Classes\OpayPayment;



class PaymentController extends Controller
{

    private $opay_secret_key;
    private $opay_public_key;
    private $opay_merchant_id;
    private $opay_country_code;
    private $opay_base_url;
    private $verify_route_name;


    public function __construct()
    {
        $this->currency = config('nafezly-payments.OPAY_CURRENCY');
        $this->opay_secret_key = config('nafezly-payments.OPAY_SECRET_KEY');
        $this->opay_public_key = config('nafezly-payments.OPAY_PUBLIC_KEY');
        $this->opay_merchant_id = config('nafezly-payments.OPAY_MERCHANT_ID');
        $this->opay_country_code = config('nafezly-payments.OPAY_COUNTRY_CODE');
        $this->opay_base_url = config('nafezly-payments.OPAY_BASE_URL');
        $this->verify_route_name = config('nafezly-payments.VERIFY_ROUTE_NAME');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('payment::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {

        $dollarToEgp = Common::getConf('dollar_to_egp') ?? 35;
        $user = Auth::user();
        $idPackage = $request->validated()['coins_id'];

        $coin = Coin::query()->find($idPackage);

        $verify_route_name = config('nafezly-payments.VERIFY_ROUTE_NAME');
        $unique_id         = uniqid();
        $egp               = $coin->usd * $dollarToEgp * 100;
        $opay_merchant_id  = $this->opay_merchant_id;
        $opay_public_key            = config('nafezly-payments.OPAY_PUBLIC_KEY');
        $response          = Http::withHeaders([
                                                   "MerchantId"    => $opay_merchant_id,
                                                   "authorization" => "Bearer " . $opay_public_key,
                                                   "content-type"  => "application/json"
                                               ])->post(config('nafezly-payments.OPAY_BASE_URL').'/cashier/create', [
            "amount"      => [
                "currency" => config('nafezly-payments.OPAY_CURRENCY'),
                "total"    => $egp
            ],
            "callbackUrl" => $verify_route_name. "?reference_id=" . $unique_id,
            "cancelUrl"   => $verify_route_name. "?reference_id=" . $unique_id,
            "country"     => "EG",
            "expireAt"    => 780,

            "productList" => [
                [
                    "description" => "You Buy {$coin->coin} => {$coin->usd}$",
                    "name"        => "Pay Coins",
                    "price"       => $egp,
                    "productId"   => $coin->id,
                    "quantity"    => 1
                ]
            ],
            "reference"   => $unique_id,
            "returnUrl"   => $verify_route_name . "?reference_id=" . $unique_id,
            "userInfo"    => [
                "userEmail"  => $user->email ?? '',
                "userId"     => $user->id ?? 0,
                "userMobile" => $user->phone ?? '',
                "userName"   => $user->name ?? ''
            ]
        ])->json();



        if($response['code']=="00000"){
            $data = [
                'payment_id'=>$unique_id,
                'redirect_url'=>$response['data']['cashierUrl'],
                'html'=>""
            ];
        }else{
            $data = [
                'payment_id'=>$unique_id,
                'redirect_url'=>"",
                'html'=>$response['message']
            ];
        }


        /*$data =
            $this->opayPayment->pay(amount: $egp, user_id: $user->id ?? 0, user_first_name: $user->name ?? 'test', user_last_name: 'test', user_email: $user->email ?? 'sdfs', user_phone: $user->phone ?? 'fsdfs');*/
        UserCoinPayment::query()->create([
            'reference_id'=>$data['payment_id'],
            'user_id'=>$user->id ,
            'coin_id'=>$coin->id,
        ]);

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('payment::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('payment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function payment_verify(Request $request) {


//        $payment = new OpayPayment();
        $test = $this->verify($request);


        $referId = $test['payment_id'];
        $isSuccess = ($test['success'] == true);
        $processData = $test['process_data'];
        $userCoinPayment = UserCoinPayment::query()->where('reference_id', $referId)->orderByDesc('id')->first();

        if ($userCoinPayment) {

            // Already credited: idempotent no-op. This callback is reachable via a
            // no-auth GET that the user can refresh, so a replay on an already-paid
            // reference must never fall through to the FAIL write below (which would
            // re-open the row and let the next refresh credit di again).
            if ($userCoinPayment->status == PaymentStatus::SUCCESS) {
                return response()->json(['status' => 'SUCCESS']);
            }

            if ($isSuccess) {
                $isContainData = key_exists('data', $processData);
                if ($isContainData && strtolower($processData['data']['status']) == PaymentStatus::FAIL) {
                    return response()->json(['status' => 'FAIL']);
                }


                if ($isContainData && (strtolower($processData['data']['status']) == PaymentStatus::SUCCESS )) {
                    // Claim + credit atomically: lock the payment row and re-read
                    // its status under the lock so a replayed callback cannot pass
                    // the "not success yet" check twice and double-credit di.
                    DB::transaction(function () use ($userCoinPayment, $processData) {
                        $locked = UserCoinPayment::query()
                            ->whereKey($userCoinPayment->getKey())
                            ->lockForUpdate()
                            ->first();

                        if (!$locked || $locked->status == PaymentStatus::SUCCESS) {
                            return;
                        }

                        $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
                        $coin = $locked->coin;

                        if ($user) {
                            $user->increment('di', $coin->coin ?? 0);
                        }

                        $locked->status   = PaymentStatus::SUCCESS;
                        $locked->order_no = $processData['data']['orderNo'];
                        $locked->save();
                    });

                    return response()->json(['status' => 'SUCCESS']);

                }elseif ($isContainData && strtolower($processData['data']['status']) == PaymentStatus::PENDING){
                    // Mark PENDING atomically: only a row still in the initial
                    // state may be moved to pending, so a stale in-memory read
                    // (taken at line ~193, before any lock) can never demote a row
                    // that a parallel request has already committed as success or
                    // failed. Mirrors the atomic FAIL write below.
                    $moved = UserCoinPayment::query()
                        ->whereKey($userCoinPayment->getKey())
                        ->where('status', PaymentStatus::INITIAL)
                        ->update([
                            'status'   => PaymentStatus::PENDING,
                            'order_no' => $processData['data']['orderNo'],
                        ]);

                    // Keep the in-memory model consistent for anything downstream.
                    if ($moved) {
                        $userCoinPayment->status   = PaymentStatus::PENDING;
                        $userCoinPayment->order_no = $processData['data']['orderNo'];
                    } else {
                        $userCoinPayment->refresh();
                    }

                    return response()->json(['status' => 'PENDING']);
                }


            }

            // Mark FAIL atomically: the WHERE clause lets the database itself
            // refuse to overwrite a row that another concurrent request has
            // already committed as SUCCESS. Guarding on the in-memory status
            // (read before any lock, line ~193) would be stale — a request that
            // read 'initial' before a parallel success committed could otherwise
            // flip success -> fail and re-open the credit window.
            $flipped = UserCoinPayment::query()
                ->whereKey($userCoinPayment->getKey())
                ->where('status', '!=', PaymentStatus::SUCCESS)
                ->update(['status' => PaymentStatus::FAIL]);

            // Keep the in-memory model consistent for anything downstream.
            $userCoinPayment->status = $flipped
                ? PaymentStatus::FAIL
                : PaymentStatus::SUCCESS;
        }

        return response()->json(['status' => 'FAIL']);

    }

    /**
     * @param Request $request
     * @return array
     */
    public function verify(Request $request): array
    {

        $data = (string)json_encode(['country' => "EG",'reference' => $request->reference_id],JSON_UNESCAPED_SLASHES);
        $auth = hash_hmac('sha512', $data, $this->opay_secret_key);
        $response = Http::withHeaders([
                                          "MerchantId"=>$this->opay_merchant_id,
                                          "authorization"=>"Bearer ".$auth
                                      ])->post($this->opay_base_url.'/cashier/status',[
            'reference'=>$request->reference_id,
            'country'=>"EG"
        ])->json();

        if($response['code']=="00000" && isset($response['data']['status']) && $response['data']['status']){
            return [
                'success' => true,
                'payment_id'=>$request->reference_id,
                'message' => __('nafezly::messages.PAYMENT_DONE'),
                'process_data' => $response
            ];

        }else{
            return [
                'success' => false,
                'payment_id'=>$request->reference_id,
                'message' => __('nafezly::messages.PAYMENT_FAILED_WITH_CODE',['CODE'=>$response['message']]),
                'process_data' => $response
            ];
        }
    }
}
