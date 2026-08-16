<?php

namespace App\Helpers;

class SignatureHelper
{
    public static function generateSignature($signatureNonce, $appKey, $timestamp)
    {
        $data = $signatureNonce . $appKey . $timestamp;
        return md5($data);
    }

    public static function generateNonce()
    {
        return bin2hex(random_bytes(8));
    }

    public static function verifySignature($signature, $signatureNonce, $appKey, $timestamp)
    {
        $expectedSignature = self::generateSignature($signatureNonce, $appKey, $timestamp);
        return hash_equals($expectedSignature, $signature);
    }
}
