<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptDataMiddleware
{

    protected $privateKey;
    public function __construct() 
    {
        $this->privateKey = file_get_contents(storage_path('app/credentials/PrivetKey.txt'));
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

     public function handle(Request $request, Closure $next)
    {
        if ($request->has('encrypted_data')) {
            $encryptedHex = $request->encrypted_data;

            // Convert the hex string to binary
            $encryptedBinary = hex2bin($encryptedHex);

            // Decrypt the data using the private key
            $decryptedData = $this->privateDecrypt2048($encryptedBinary, $this->privateKey);

            if ($decryptedData) {
                // Merge decrypted data into the request
                $request->merge($decryptedData);
            } else {
                return Common::apiResponse(0, 'Decryption failed', null, 400);
            }
        }

        return $next($request);
    }

    protected function privateDecrypt2048($encrypted = '', $privateKeyFile)
    {
        if (!is_string($encrypted)) {
            return false;
        }

        extension_loaded('openssl') or die('PHP requires OpenSSL extension support');

        // Load the private key resource
        $key = $this->loadPrivateKey($privateKeyFile);

        $decrypted = "";

        // Split the encrypted data into chunks
        $enArray = str_split($encrypted, 2048 / 8);

        foreach ($enArray as $va) {
            $decryptedTemp = "";
            $ciphertext = $va;

            // Decrypt each chunk using the private key
            $return_de = openssl_private_decrypt($ciphertext, $decryptedTemp, $key, OPENSSL_PKCS1_PADDING);
            if (!$return_de) {
                return false;
            }
            $decrypted .= $decryptedTemp;
        }

        return json_decode($decrypted, true);
    }

    // Helper method to load the private key
    protected function loadPrivateKey($privateKeyFile)
    {
        // Load the private key from a file
        $privateKey = $privateKeyFile;

        // Ensure the key is valid
        if (!$privateKey) {
            throw new \Exception("Private key file could not be read.");
        }

        $private_key_resource = openssl_pkey_get_private($privateKey);

        if (!$private_key_resource) {
            throw new \Exception("Private key is not valid or could not be loaded.");
        }

        return $private_key_resource;
    }
}