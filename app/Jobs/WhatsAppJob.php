<?php

namespace App\Jobs;

use App\Facades\RedisService;
use GuzzleHttp\Client;
use Database\Seeders\config;
use Illuminate\Bus\Queueable;
use PHPUnit\Event\Telemetry\Info;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $phone;
    private $message;
    private $token;

    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    public function handle(): void
    {
       // $url = (string)config('view.whatsapp_url');
        // $token = (string)config('view.whatsapp_token');
        // $to =  $this->phone;
        // $body =$this->message ;
        $safwaUrl = config('whatsappauth.base_url'). '/api/send-code-service';

        $token = RedisService::get('whatsapp_token');
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get($safwaUrl, [
                'code' => (string) $this->message,
                'phone' => (string) $this->phone,
            ]);
        } catch (RequestException $e) {
        }

        // $client = new Client();
        // try {
        //         $client->post($url, [
        //         'form_params' => [
        //             'token' => (string)$token,
        //             'to' =>(string) $to,
        //             'body' => (string)$body,
        //         ],
        //         'headers' => [
        //             'Content-Type' => 'application/x-www-form-urlencoded',
        //         ],
        //     ]);
        // } catch (RequestException $e) {
        // }
    }
}
