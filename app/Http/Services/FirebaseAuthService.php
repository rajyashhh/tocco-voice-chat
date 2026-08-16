<?php

namespace App\Http\Services;

use App\Helpers\Common;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;


class FirebaseAuthService
{
    protected Auth $auth;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(Common::firebaseCredentials());

        $this->auth = $factory->createAuth();
    }

    /**
     * Create Firebase Custom Token using UID
     */
    public function createCustomToken(string $uid): string
    {
        return $this->auth->createCustomToken($uid)->toString();
    }

    public function createGuest(): array
    {
        $user = $this->auth->createUser([
            'disabled' => false,
        ]);

        $uid = $user->uid;

        $customToken = $this->auth->createCustomToken($uid)->toString();

        return [
            'uid'   => $uid,
            'token' => $customToken,
        ];
    }
}
