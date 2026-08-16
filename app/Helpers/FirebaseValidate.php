<?php

namespace App\Helpers;

use Kreait\Firebase\Factory;

class FirebaseValidate
{

    /**
     * @param $idToken
     * @throw FailedToVerifyToken || \Exception
     * @return mixed|null
     */
    public static function validateIdToken($idToken)
    {
        $factory = (new Factory())->withServiceAccount(Common::firebaseCredentials());
        $auth = $factory->createAuth();
        $verifiedIdToken = $auth->verifyIdToken($idToken);

        // Authentication token is valid
        return $verifiedIdToken->claims()->get('sub');
    }

    /**
     * Verify the Firebase ID token and return its claims as an array
     * (including phone_number and sub).
     *
     * @param $idToken
     * @throw FailedToVerifyToken || \Exception
     * @return array
     */
    public static function verifyAndGetClaims($idToken): array
    {
        $factory = (new Factory())->withServiceAccount(Common::firebaseCredentials());
        $auth = $factory->createAuth();
        $verifiedIdToken = $auth->verifyIdToken($idToken);

        return $verifiedIdToken->claims()->all();
    }
}
