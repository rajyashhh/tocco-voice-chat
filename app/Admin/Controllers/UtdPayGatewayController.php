<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UtdPayGatewayController extends MainController
{
    protected $title = 'UTD Payment Gateways';

    public function index(Content $content)
    {
        $gateways = $this->fetchGateways();

        return $content
            ->title('UTD Payment Gateways')
            ->description('Manage payment gateway status')
            ->body(view('admin.utd-pay-gateways.index', compact('gateways')));
    }

    public function toggle(Request $request): JsonResponse
    {
        $gatewayId = $request->input('gateway_id');
        $enabled = $request->boolean('enabled');

        try {
            $response = Http::timeout(15)->post($this->getApiUrl(), [
                'apiKey' => config('utd.api_key'),
                'gatewayId' => $gatewayId,
                'enabled' => $enabled,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => $data['success'] ?? false,
                    'message' => $data['message'] ?? 'Updated',
                ]);
            }

            $error = $response->json('error') ?? 'Failed to update gateway';
            return response()->json(['status' => false, 'message' => $error], $response->status());
        } catch (\Exception $e) {
            Log::error('UTD toggle gateway error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Connection error'], 500);
        }
    }

    private function fetchGateways(): array
    {
        try {
            $response = Http::timeout(15)->get($this->getApiUrl(), [
                'apiKey' => config('utd.api_key'),
            ]);

            if ($response->successful()) {
                return $response->json('gateways') ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('UTD fetch gateways error: ' . $e->getMessage());
            return [];
        }
    }

    private function getApiUrl(): string
    {
        return config('utd.admin_url');
    }
}
