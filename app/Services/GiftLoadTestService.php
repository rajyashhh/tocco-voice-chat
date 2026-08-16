<?php

namespace App\Services;

use App\Models\User;
use App\Models\Gift;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Http;

class GiftLoadTestService
{
    /**
     * Run a load test for sending gifts.
     *
     * @param array $data [
   
     * @return array
     * @throws \Exception
     */
    public function run(array $data)
    {
        // جلب بيانات المرسل
        $response = Http::withToken($data['token'])
                        ->get($data['url'] . '/api/my-data');

        if ($response->failed()) {
            throw new \Exception("Token غير صالح أو لا يمكن جلب بيانات المرسل");
        }

        $senderData = $response->json('data');
        if (!isset($senderData['id'])) {
            throw new \Exception("لم يتم العثور على ID المرسل");
        }

        $sender = User::findOrFail($senderData['id']);
        $receiver = User::findOrFail($data['toUid']);
        $gift = Gift::findOrFail($data['id']);

        $giftValue = $gift->price;
        $giftReceive = $gift->price;

        // أرصدة قبل الإرسال
        $beforeSender = $sender->di;
        $beforeReceiver = $receiver->monthly_diamond_received;

        // تهيئة Client
        $client = new Client([
            'base_uri' => $data['url'],
            'timeout'  => 10,
            'verify'   => false,
        ]);

        $promises = [];

        for ($i = 1; $i <= $data['count']; $i++) {
            $promises[$i] = $client->postAsync('/api/gifts/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $data['token'],
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'id' => $data['id'],
                    'owner_id' => $data['owner_id'],
                    'toUid' => $data['toUid'],
                    'num' => $data['num']
                ]
            ]);
        }

        $results = Utils::settle($promises)->wait();

        // ملخص النتائج
        $summary = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $totalSentGifts = 0; // العدد الفعلي للهدايا المرسلة

        foreach ($results as $index => $res) {
            if ($res['state'] === 'fulfilled') {
                $body = json_decode($res['value']->getBody(), true);
                $isSuccess = !empty($body['success']);

                if ($isSuccess) {
                    $summary['success']++;
                    $totalSentGifts += $data['num'];
                } else {
                    $summary['failed']++;
                }

                $summary['errors'][] = [
                    'index' => $index,
                    'status' => $isSuccess ? 'SUCCESS' : 'FAILED',
                    'response' => $body
                ];

            } else {
                $summary['failed']++;
                $summary['errors'][] = [
                    'index' => $index,
                    'status' => 'FAILED',
                    'error' => $res['reason']->getMessage()
                ];
            }
        }

        // حساب الرصيد المتوقع بعد الإرسال
        $expectedSender = $beforeSender - ($giftValue * $totalSentGifts);
        $expectedReceiver = $beforeReceiver + ($giftReceive * $totalSentGifts);


        $afterSender = $sender->fresh()->di;
     foreach ($results as $index => $res) {
             usleep(200000); 
            $receiver2 = User::findOrFail($data['toUid']);
            $afterReceiver = $receiver2->monthly_diamond_received;
        }


        return [
            'summary' => $summary,
            'before_sender' => $beforeSender,
            'after_sender' => $afterSender,
            'expected_sender' => $expectedSender,
            'before_receiver' => $beforeReceiver,
            'after_receiver' => $afterReceiver,
            'expected_receiver' => $expectedReceiver,
            'gift_value' => $giftValue * $data['count'] ,
            'gift_receive' => $giftReceive * $data['count'] ,
            'sent_gifts' => $totalSentGifts * $data['count'],
            'failed_gifts' => $summary['failed'] * $data['num'],
            'num_per_request' => $data['num'],
        ];
    }


     public function luckyRun(array $data)
    {

    $appPercentage  = getGiftPercentage('app_wallet_lucky_gift') / 10;
    $roomrPercentage = getGiftPercentage('owner_lucky_gift') / 10;
    $hostPercentage  = getGiftPercentage('host_lucky_gift') / 10;

    // جلب بيانات المرسل
    $response = Http::withToken($data['token'])
                    ->get($data['url'] . '/api/my-data');

    if ($response->failed()) {
        throw new \Exception("Token غير صالح أو لا يمكن جلب بيانات المرسل");
    }

    $senderData = $response->json('data');
    if (!isset($senderData['id'])) {
        throw new \Exception("لم يتم العثور على ID المرسل");
    }

    $sender = User::findOrFail($senderData['id']);
    $receiver = User::findOrFail($data['toUid']);
    $gift = Gift::findOrFail($data['id']);

    $giftValue = $gift->price;
    $giftReceive = $gift->price;

    $beforeSender = $sender->di;
    $beforeReceiver = $receiver->monthly_diamond_received;

    // إعداد العميل للطلبات المتزامنة
    $client = new Client([
        'base_uri' => $data['url'],
        'timeout'  => 10,
        'verify'   => false,
    ]);

    $promises = [];
    for ($i = 1; $i <= $data['count']; $i++) {
        $promises[$i] = $client->postAsync('/api/gifts/send-lucky-gift-combo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'id' => $data['id'],
                'owner_id' => $data['owner_id'],
                'toUid' => $data['toUid'],
                'num' => $data['num']
            ]
        ]);
    }

    $results = Utils::settle($promises)->wait();

    $summary = [
        'success' => 0,
        'failed' => 0,
        'errors' => []
    ];

    $totalSentGifts = 0;
    $totalWins       = 0;
    $totalAppShare   = 0;
    $totalRoomrShare = 0;
    $totalHostShare  = 0;

    foreach ($results as $index => $res) {
        if ($res['state'] === 'fulfilled') {
            $body = json_decode($res['value']->getBody(), true);
            $isSuccess = !empty($body['success']);

            if ($isSuccess) {
                $summary['success']++;
                $totalSentGifts += $data['num'];

                // حساب المكتسبات والنسب لكل كومبو
                foreach ($body['data']['combo'] as $combo) {
                    $winCoins = $combo['data']['win_coins'] ?? 0;

                    $totalWins       += $winCoins;
                    // $totalAppShare   += $winCoins * $appPercentage;
                    // $totalRoomrShare += $winCoins * $roomrPercentage;
                    // $totalHostShare  += $winCoins * $hostPercentage;
                }
            } else {
                $summary['failed']++;
            }

            $summary['errors'][] = [
                'index' => $index,
                'status' => $isSuccess ? 'SUCCESS' : 'FAILED',
                'response' => $body
            ];

        } else {
            $summary['failed']++;
            $summary['errors'][] = [
                'index' => $index,
                'status' => 'FAILED',
                'error' => $res['reason']->getMessage()
            ];
        }
    }

    $expectedSender   = $beforeSender - ($giftValue * $totalSentGifts) + $totalWins;
    $hostGain = $giftValue * $totalSentGifts * $hostPercentage;
    $expectedReceiver = $beforeReceiver + $hostGain;
    $totalPrice =$giftValue * $totalSentGifts;

    $totalAppShare   = $totalPrice * $appPercentage;
    $totalRoomrShare = $totalPrice * $roomrPercentage;
    $totalHostShare  = $totalPrice * $hostPercentage;

 

     foreach ($results as $index => $res) {
             usleep(800000); 
            $afterSender = $sender->fresh()->di + $totalWins;
            $receiver2 = User::findOrFail($data['toUid']);
            $afterReceiver = $receiver2->monthly_diamond_received;
        }


        return [
                'summary' => $summary,
                'before_sender' => $beforeSender,
                'after_sender' => $afterSender,
                'expected_sender' => $expectedSender,
                'before_receiver' => $beforeReceiver,
                'after_receiver' => $afterReceiver,
                'expected_receiver' => $expectedReceiver,
                'gift_value' => $giftValue * $data['count'],
                'gift_receive' => $giftReceive * $data['count'],
                'sent_gifts' => $totalSentGifts * $data['num'],
                'failed_gifts' => $summary['failed'] * $data['num'],
                'num_per_request' => $data['num'],
                'total_wins' => $totalWins,
                'total_app_share' => $totalAppShare,
                'total_roomr_share' => $totalRoomrShare,
                'total_host_share' => $totalHostShare,
        ];
    }
}


