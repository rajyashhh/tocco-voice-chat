<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFawrySignature
{

    public function handle(Request $request, Closure $next): Response
    {
        $responseData           = $request->all();
        $secureKey              = config("services.fawry.fawry_secret");
        $fawryRefNumber         = $responseData['fawryRefNumber'];
        $merchantRefNumber      = $responseData['merchantRefNumber'];
        $paymentAmount          = number_format($responseData['paymentAmount'], 2, '.', '');
        $orderAmount            = number_format($responseData['orderAmount'], 2, '.', '');
        $orderStatus            = $responseData['orderStatus'];
        $paymentMethod          = $responseData['paymentMethod'];
        $paymentReferenceNumber = @$responseData['paymentRefrenceNumber'];
        $concatenatedString     = $fawryRefNumber . $merchantRefNumber . $paymentAmount . $orderAmount . $orderStatus . $paymentMethod . $paymentReferenceNumber . $secureKey;
        $generatedSignature     = hash('sha256', $concatenatedString);
        $messageSignature       = $request->messageSignature;

        if ($generatedSignature !== $messageSignature) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        return $next($request);
    }
}
