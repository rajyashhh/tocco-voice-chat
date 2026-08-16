<?php

namespace App\Tik\Services;

use App\Exceptions\CValidationException;
use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\DevicesTokenHistory;
use App\Models\Profile;
use App\Models\User;
use App\Tik\Repositories\CountryRepository;
use App\Tik\Repositories\UserRepository;
use DB;
use Google_Client;
use Google\Client as GoogleClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;
use Modules\SwitchAccount\Http\Services\SwitchAccountServices;
use Modules\SwitchAccount\Traits\SwithAccountLogin;

use function request;

class AuthService
{
    use SwithAccountLogin;
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CountryRepository $countryRepository,
    ) {}

    public function verifyGoogleToken($id_token)
    {
        if (!$id_token) {
            return false;
        }

        if (substr_count($id_token, '.') !== 2) {
            throw new \Exception('Wrong number of segments in ID token');
        }
        $googleResponse = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $id_token
        ]);
        if ($googleResponse->successful()) {
            if ($googleResponse->json('aud') === Common::whiteLabel('google_client_id', 'app.google_client_id')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function verifyGoogleTokenLogin($idToken)
    {
        $client = new Google_Client(['client_id' => Common::whiteLabel('google_client_id', 'app.google_client_id')]);
        $payload = $client->verifyIdToken($idToken);
        return $payload ? $payload : false;
    }

    /**
     * @throws \Throwable
     */
    public function registration($request)
    {
        $phone = str_replace([' ', '-', '/', '{', '}', '_', '(', ')'], '', $request->phone);

        if ($this->userRepository->findByPhoneUser($request->phone))  throw new \Exception('already exists');

        $trashedUser = $this->userRepository->findByPhoneUserTrashed($request->phone);

        DB::beginTransaction();
        try {
            $lat = $request->lat;
            $long = $request->long;
            $iso = $request->iso;
            $countryId = null;

            if ($trashedUser) {
                $trashedUser->restore();

                $trashedUser->password = $request->password;

                $user = $trashedUser;
            } else {
                $data = [
                    'phone' => $phone,
                    'firebase_uuid' => $request->uuid,
                    'password' => $request->password,
                    'status' => 1
                ];

                if ($iso) {
                    $country = Country::where('iso', strtoupper($iso))->first();
                    if ($country) {
                        $countryId = $country->id;
                    }
                }

                // The dial code is always present in the phone at signup —
                // derive the country from it when the client sent no iso.
                if (!$countryId) {
                    $countryId = $this->countryIdFromPhone($phone);
                }

                //                if (!$countryId && $lat && $long){
                //                    $countryId = getCountryIdFromLatLong($lat, $long);
                //                }

                if ($countryId) {
                    $data['country_id'] = $countryId;
                }
               // if (!empty($request['device_token']))  $this->devicesTokenHistory($request['device_token']);

                try {
                    if (!empty($request['device_token'])) {
                        $this->devicesTokenHistory($request['device_token']);
                    }
                } catch (CValidationException $e) { 
                    throw $e;
                } catch (\Exception $e) { 
                    logger()->error('Technical error in device token history', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Something went wrong');
                }
                $user = $this->userRepository->create($data);
            }

            if (request('tags') && is_array(request('tags'))) {
                $user->tags()->attach(request('tags'));
            }
            //            if (!$request->country_id) {
            //                $country = $this->countryRepository->findByPhoneCode('101');
            //                $user->country_id = @$country->id;
            //            }
            $user->is_points_first = 1;
            $user->save();
            $token = $user->createToken('api_token')->plainTextToken;
            //  UserHandling::AddUserVip($user, 'register');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [$user, $token];
    }

    public function loginWithPassword($request)
    {
        $user = $this->userRepository->findByPhoneUser($request['phone']);
        if (!$user || !Hash::check($request['password'], $user->password)) {
            throw new \Exception('credentials does`t match');
        }
        //  $user->firebase_uuid = $request['uuid'];
        //  $user->save();

        $device_token = $request['device_token'] ?? null;
        $this->rule($user, '', $device_token, $request);

        // $this->rule($user, '', @$request['device_token'], $request);

        $token = $user->createToken('api_token')->plainTextToken;
        $this->userRepository->updateIsLogout($user, 0);
        return [$user, $token];
    }

    /**
     * @throws \Exception
     */
    public function loginWithGoogle($request)
    {
        if (!$request['id_token']) return Common::apiResponse(false, 'google id token missing', [], 422);
        // Audience comes from the panel (settings.google_client_id) with .env
        // fallback; when both are blank the library skips the aud check, so a
        // deployment that never set it keeps its current behavior.
        $client = new Google_Client(['client_id' => Common::whiteLabel('google_client_id', 'app.google_client_id')]);
        $payload = $client->verifyIdToken($request['id_token']);
        if (!$payload) {
            return Common::apiResponse(false, 'Google ID Token not found or invalid', [], 422);
        }
       // $google_id = $payload['sub'];

        $google_id = $request['google_id'] ?? null;

        if (!$google_id) {
            return Common::apiResponse(false, 'Google ID not found in token or request', [], 422);
        }

        $user = $this->userRepository->findByGoogleId($request['google_id']);
        $is_new = false;
        if (!$user) {
            $trashedEmail = $this->userRepository->checkTrashedEmail($request['email'], $request['google_id']);
            if ($trashedEmail) {
                $trashedEmail->deleted_at = null;
                $trashedEmail->Save();
                $user = $trashedEmail;
                $resource = [
                    'google_id' => $request['google_id'],
                    'status' => true,
                    'email' => $request['email'],
                    'name' => $request['name'],
                    'firebase_uuid' => $request['uuid'],

                ];
            } else {
                $email =    $this->userRepository->findByEmail($request['email']);
                if ($email) return Common::apiResponse(false, 'you used this email before', [], 422);
                $country = $this->countryRepository->findByPhoneCode('101');
                $data = [
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'google_id' => $request['google_id'],
                    'country_id' => @$country->id ?: null,
                    'is_points_first' => 1,
                    'status' => true,
                    'firebase_uuid' => $request['uuid'],

                ];

                $lat = $request['lat'];
                $long = $request['long'];
                $iso = $request['iso'];
                $countryId = null;

                if ($iso) {
                    $country = Country::where('iso', strtoupper($iso))->first();
                    if ($country) {
                        $countryId = $country->id;
                    }
                }

                if ($countryId) {
                    $data['country_id'] = $countryId;
                }

                // Check device account limit BEFORE creating new Google user
                try {
                    if (!empty($request['device_token'])) {
                        $this->devicesTokenHistory($request['device_token']);
                    }
                } catch (CValidationException $e) { 
                    throw $e;
                } catch (\Exception $e) { 
                    logger()->error('Technical error in device token history', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Something went wrong');
                }
                $user = $this->userRepository->create($data);

                $is_new = true;
                try {
                    $this->storeImage($request, $data, $user);
                } catch (\Exception $e) {
                    $isBusinessRule = $e->getMessage() === __('api_responses.gifImage');
                    logger()->{$isBusinessRule ? 'info' : 'error'}('Failed to store user image', [
                        'user_id' => $user->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }

                $tags = request('tags');
                if ($tags && is_array($tags)) {
                    try {
                        $user->tags()->attach($tags);
                    } catch (\Exception $e) {
                        logger()->error('Failed to attach user tags', [
                            'user_id' => $user->id ?? null,
                            'tags' => $tags,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        $user->save();
        $device_token = $request['device_token'] ?? null;
        $this->rule($user, '', $device_token, $request);
        $token = $user->createToken('api_token')->plainTextToken;
        $this->userRepository->updateIsLogout($user, 0, $is_new);
        return [$user, $token, []];
    }

    /**
     * @throws \Exception
     */
    // public function storeImage($request, $data, $user)
    // {
    //     // Handle UploadedFile instances
    //     if (isset($request['image']) && $request['image'] instanceof UploadedFile) {
    //         $img = $request['image'];
    //         $imageType = $img->getClientOriginalExtension();
    //         if (!$imageType) {
    //             $mime = $img->getMimeType();
    //             $imageType = match ($mime) {
    //                 'image/jpeg' => 'jpg',
    //                 'image/png'  => 'png',
    //                 'image/gif'  => 'gif',
    //                 'image/webp' => 'webp',
    //                 default      => 'jpg',
    //             };
    //         }
    //         if ($imageType == 'gif' && !Common::hasInPack($user->id, 22, false)) {
    //             throw new \Exception(__('api_responses.gifImage'));
    //         }

    //         $user->profile_count += 1;
    //         $user->save();
    //         $user->load('profile');
    //         $profile = $user->profile;
    //         if (!$profile) {
    //             $profile = Profile::create([
    //                 'gender' => null,
    //                 'birthday' => null,
    //                 'province' => null,
    //                 'city' => null,
    //                 'country' => null,
    //                 'user_id' => @$user->id,
    //             ]);
    //         }

    //         $newImagePass = Common::uploadProfileUser('profile', $img, $profile->id, $user->profile_count);

    //         $profile->avatar = $newImagePass;
    //         $profile->save();

    //         return $profile;
    //     }

    //     // Handle image URLs from Google, Apple, etc.
    //     if (isset($request['image']) && is_string($request['image']) && !empty($request['image'])) {
    //         try {
    //             $imageUrl = $request['image'];

    //             // Download the image from URL
    //             $response = Http::get($imageUrl);
    //             if (!$response->successful()) {
    //                 Log::warning('Failed to download image from URL', ['url' => $imageUrl]);
    //                 return null;
    //             }

    //             $imageContent = $response->body();
    //             if (empty($imageContent)) {
    //                 Log::warning('Image content is empty from URL', ['url' => $imageUrl]);
    //                 return null;
    //             }

    //             // Determine the file extension from URL
    //             $extension = 'jpg'; // default
    //             if (preg_match('/\.([a-z]+)(?:\?|$)/i', $imageUrl, $matches)) {
    //                 $ext = strtolower($matches[1]);
    //                 // Validate extension
    //                 if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    //                     $extension = $ext;
    //                 }
    //             }

    //             $user->profile_count += 1;
    //             $user->save();
    //             $user->load('profile');
    //             $profile = $user->profile;
    //             if (!$profile) {
    //                 $profile = Profile::create([
    //                     'gender' => null,
    //                     'birthday' => null,
    //                     'province' => null,
    //                     'city' => null,
    //                     'country' => null,
    //                     'user_id' => @$user->id,
    //                 ]);
    //             }

    //             // Create the filename with proper extension
    //             $fileName = $profile->id . '_' . $user->profile_count . '.' . $extension;
    //             $filePath = 'profile' . DIRECTORY_SEPARATOR . $fileName;

    //             // Store the image directly
    //             Storage::put($filePath, $imageContent, config('filesystems.default'));


    //             $profile->avatar = $filePath;
    //             $profile->save();

    //             return $profile;
    //         } catch (\Exception $e) {
    //             Log::error('Failed to store image from URL', [
    //                 'user_id' => $user->id ?? null,
    //                 'image_url' => $request['image'] ?? null,
    //                 'error' => $e->getMessage()
    //             ]);
    //             // Don't rethrow - continue without image
    //             return null;
    //         }
    //     }
    // }

    public function storeImage(array $request, array $data, User $user): ?Profile
    {
        if (!isset($request['image'])) {
            return null;
        }

        if ($request['image'] instanceof UploadedFile) {
            return $this->storeUploadedImage($request['image'], $user);
        }

        if (is_string($request['image']) && !empty($request['image'])) {
            return $this->storeImageFromUrl($request['image'], $user);
        }

        return null;
    }

    private function storeUploadedImage(UploadedFile $image, User $user): Profile
    {
        $extension = $this->getImageExtension($image);

        if ($extension === 'gif' && !Common::hasInPack($user->id, 22, false)) {
            throw new \Exception(__('api_responses.gifImage'));
        }

        $profile = $this->getOrCreateProfile($user);

        $path = Common::uploadProfileUser(
            'profile',
            $image,
            $profile->id,
            $user->profile_count
        );

        //    Log:: info('Uploaded profile image', [
        //         'extension' => $extension,
        //         'path' => $path,
        //     ]);

        $profile->update(['avatar' => $path]);

        return $profile;
    }
    private function storeImageFromUrl(string $url, User $user): ?Profile
    {
        try {
            // Security: Validate URL against SSRF attacks
            $validation = \App\Helpers\UrlValidator::validateUrl($url, true);

            if (!$validation['valid']) {
                Log::warning('SSRF attempt blocked in registration', [
                    'url' => $url,
                    'error' => $validation['error'],
                    'user_id' => $user->id,
                    'ip' => request()->ip()
                ]);
                return null;
            }

            // Only allow HTTPS for external images
            if (!str_starts_with($url, 'https://')) {
                Log::warning('Non-HTTPS URL blocked in registration', [
                    'url' => $url,
                    'user_id' => $user->id,
                    'ip' => request()->ip()
                ]);
                return null;
            }

            // Download with security restrictions
            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => true,
                    'allow_redirects' => [
                        'max' => 2,
                        'strict' => true
                    ]
                ])
                ->get($url);

            if (!$response->successful() || empty($response->body())) {
                Log::warning('Failed to download image', [
                    'url' => $url,
                    'status' => $response->status()
                ]);
                return null;
            }

            // Validate content type
            $contentType = $response->header('Content-Type');
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!$contentType || !in_array(strtolower($contentType), $allowedTypes)) {
                Log::warning('Invalid image content type from URL', [
                    'url' => $url,
                    'content_type' => $contentType,
                    'user_id' => $user->id
                ]);
                return null;
            }

            // Validate size (10MB max)
            $body = $response->body();
            if (strlen($body) > 10485760) {
                Log::warning('Image from URL exceeds size limit', [
                    'url' => $url,
                    'size' => strlen($body),
                    'user_id' => $user->id
                ]);
                return null;
            }

            // Map content type to safe extension
            $safeExtension = match (strtolower($contentType)) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $profile = $this->getOrCreateProfile($user);

            // Use safe extension instead of URL-derived one
            $fileName = "{$profile->id}_{$user->profile_count}.{$safeExtension}";
            $path = "profile/{$fileName}";

            Storage::put($path, $body, config('filesystems.default'));

            Log::info('Stored profile image from URL', [
                'user_id' => $user->id,
                'path' => $path,
                'content_type' => $contentType
            ]);

            $profile->update(['avatar' => $path]);

            return $profile;
        } catch (\Throwable $e) {
            Log::error('Image URL upload failed', [
                'user_id' => $user->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
    private function getOrCreateProfile(User $user): Profile
    {
        $user->increment('profile_count');
        $user->load('profile');

        return $user->profile ?? Profile::create([
            'user_id'  => $user->id,
            'gender'   => null,
            'birthday' => null,
            'province' => null,
            'city'     => null,
            'country'  => null,
        ]);
    }
    private function getImageExtension(UploadedFile $image): string
    {
        return $image->getClientOriginalExtension()
            ?: match ($image->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
    }
    private function getExtensionFromUrl(string $url): string
    {
        if (preg_match('/\.([a-z]+)(?:\?|$)/i', $url, $matches)) {
            $ext = strtolower($matches[1]);
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return $ext;
            }
        }

        return 'jpg';
    }



    //     public function loginWithApple($request, $unique_id)
    //     {
    //         $user = $this->userRepository->findByAppleId($unique_id);
    //         if (!$user) {
    //             $data = [
    //                 'name' => implode('@', explode('@', $request['email'], -1)),
    //                 'email' => @$request['email'],
    //                 'apple_id' => $unique_id,
    //             ];

    //             $lat = $request['lat'];
    //             $long = $request['long'];
    //             $iso = $request['iso'];
    //             $countryId = null;

    //             if ($iso){
    //                 $country = Country::where('iso', strtoupper($iso))->first();
    //                 if ($country) {
    //                     $countryId = $country->id;
    //                 }
    //             }

    // //            if (!$countryId && $lat && $long){
    // //                $countryId = getCountryIdFromLatLong($lat, $long);
    // //            }

    //             if ($countryId) {
    //                 $data['country_id'] = $countryId;
    //             }

    //             $user = $this->userRepository->create($data);
    //         }
    //         $this->rule($user, '', @$request['device_token'], $request);

    //         $token = $user->createToken('api_token')->plainTextToken;
    //         $this->userRepository->updateIsLogout($user, 0);
    //         return [$user, $token];
    //     }

    public function loginWithApple($request, $unique_id)
    {
        $appleUser = $this->userRepository->findByAppleId($unique_id);

        if ($appleUser) {

            if (!$request['email']) {
                $request['email'] = $appleUser->email;
            }

            return $this->finishLogin($appleUser, $request);
        }

        $email = $request['email'];
        $name  = $request['name'] ?? ($email ? explode("@", $email)[0] : "AppleUser-" . rand(1000, 9999));

        if ($email && $this->userRepository->emailExists($email)) {
            $email = null;
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'apple_id' => $unique_id,
        ];

        if (!empty($request['iso'])) {
            $country = Country::where('iso', strtoupper($request['iso']))->first();
            if ($country) $data['country_id'] = $country->id;
        }
        if (!empty($request['device_token']))  $this->devicesTokenHistory($request['device_token']);

        $newUser = $this->userRepository->create($data);

        return $this->finishLogin($newUser, $request);
    }


    /**
     * Longest dial-code prefix match against countries.phone_code.
     * Returns null when the phone has no recognizable country prefix.
     */
    private function countryIdFromPhone(?string $phone): ?int
    {
        $digits = ltrim(preg_replace('/\D+/', '', (string) $phone), '0');
        if ($digits === '') {
            return null;
        }

        static $dialMap = null;
        if ($dialMap === null) {
            $dialMap = Country::whereNotNull('phone_code')
                ->where('phone_code', '!=', '')
                ->get(['id', 'phone_code'])
                ->map(fn ($c) => ['id' => $c->id, 'dial' => ltrim($c->phone_code, '+')])
                ->filter(fn ($c) => $c['dial'] !== '' && ctype_digit($c['dial']))
                ->sortByDesc(fn ($c) => strlen($c['dial']))
                ->values();
        }

        foreach ($dialMap as $c) {
            if (str_starts_with($digits, $c['dial'])) {
                return (int) $c['id'];
            }
        }

        return null;
    }

    private function finishLogin($user, $request)
    {
        $this->rule($user, '', @$request['device_token'], $request);

        $token = $user->createToken('api_token')->plainTextToken;

        $this->userRepository->updateIsLogout($user, 0);

        return [$user, $token];
    }


    public function loginWithHuawei($data)
    {
        $user = $this->userRepository->findByHuawei($data['huawei_id']);
        if (!$user) {
            if ($this->userRepository->checkEmail($data['huawei_id'])) {
                throw new \Exception('email already taken',);
            } else {
                $verify = $this->verifyHuaweiID($data['id_token'], $data['huawei_id']);
                if ($verify == false) {
                    throw new \Exception('هناك مشكله حاول مره اخري');
                }
                $country = $this->countryRepository->findByPhoneCode('101');
                $dataUser = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'huawei_id' => $data['huawei_id'],
                    'country_id' => @$country->id ?: 0,
                    'is_points_first' => 1,
                ];

                $lat = $data['lat'];
                $long = $data['long'];
                $iso = $data['iso'];
                $countryId = null;

                if ($iso) {
                    $country = Country::where('iso', strtoupper($iso))->first();
                    if ($country) {
                        $countryId = $country->id;
                    }
                }

                //                if (!$countryId && $lat && $long){
                //                    $countryId = getCountryIdFromLatLong($lat, $long);
                //                }

                if ($countryId) {
                    $data['country_id'] = $countryId;
                }
                if (!empty($data['device_token']))  $this->devicesTokenHistory($data['device_token']);

                $user = $this->userRepository->create($dataUser);
            }
        }

        $this->rule($user, '', @$data['device_token'], $data);

        $token = $user->createToken('api_token')->plainTextToken;
        $this->userRepository->updateIsLogout($user, 0);
        return [$user, $token];
    }

    public function recallAccount($request)
    {
        $user = $this->userRepository->findByTrashedEmail($request['email'], $request['google_id']);
        if (!$user)  throw new \Exception('something wrong');
        if ($request['status'] == 0) {
            $user->forceDelete();
            $data = [
                'name' => @$request['name'],
                'email' => $request['email'],
                'google_id' => $request['google_id'],
                'device_token' => @$request['device_token'],
                'status' => 1
            ];
            $user = $this->userRepository->create($data);
        } else {
            $user->restore();
        }
        $this->rule($user, '', @$request['device_token'], $request);

        $token = $user->createToken('api_token')->plainTextToken;
        $this->userRepository->updateIsLogout($user, 0);
        return [$user, $token];
    }




    public function logoutAsConfiguration($user)
    {
        if (Common::getConf('login_from_only_one_device') == 'yes') {
            //            $user->tokens()->delete();
        }
    }


    public function rule($user, $type, $deviceToken, $data)
    {
        if ($this->checkIsSameAccount($user->id, $data)) {
            throw new \Exception(__($this->getSameAccountMessage()));
        }
        $message = UserHandling::hasReasonOfBan($user->uuid, request());

        if ($message) {
            throw new \Exception($message);
        }

        // Check device account limit before allowing login
        if (!empty($deviceToken)) {
            $this->checkDeviceAccountLimit($user->id, $deviceToken);
        }

        $this->userRepository->updateDeviceToken($user, $deviceToken);
        (new SwitchAccountServices())->saveDeviceUser($user->id, $deviceToken);
        $this->logoutAsConfiguration($user);
        return true;
    }


    public function verifyHuaweiID($idToken, $huaweiId)
    {
        $url = 'https://oauth-login.cloud.huawei.com/oauth2/v3/tokeninfo';

        $response = Http::asForm()->post($url, [
            'id_token' => $idToken
        ]);
        if ($response->successful()) {
            $response = $response->json();
            if ($huaweiId == $response['sub']) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    private function devicesTokenHistory($deviceToken)
    {
        $register_account = (int)(Common::getSettingValue('register_account') ?? 3);

        // STRICT POLICY: Count ALL accounts registered on this device (even logged out ones)
        // This prevents users from bypassing the limit by logging out and registering new accounts
        $actualUsersCount = User::where('device_token', $deviceToken)
            ->where('device_token', '!=', '')
            ->whereNotNull('device_token')
            ->where('is_logout', 0)
            ->count();

        if ($actualUsersCount >= $register_account) {
            \Log::warning('Device account limit exceeded - registration blocked (STRICT)', [
                'device_token' => $deviceToken,
                'total_accounts_count' => $actualUsersCount,
                'limit' => $register_account,
                'policy' => 'strict - all accounts counted'
            ]);
            throw new CValidationException(__('max_accounts_reached'));
        }

        // Update devices_token_histories for tracking purposes
        $record = DevicesTokenHistory::where('device_token', $deviceToken)->first();

        if ($record) {
            $record->increment('count');
        } else {
            DevicesTokenHistory::create(['device_token' => $deviceToken, 'count' => 1]);
        }
    }

    /**
     * Check if device has reached the maximum allowed accounts limit
     * Used during login to prevent users from logging in with too many accounts on same device
     *
     * STRICT POLICY: Counts ALL accounts registered on this device (even logged out ones)
     * This prevents users from bypassing the limit by creating many accounts
     *
     * @param int $userId - The user trying to login
     * @param string $deviceToken - The device token
     * @throws CValidationException if limit exceeded
     */
    private function checkDeviceAccountLimit($userId, $deviceToken)
    {
        $register_account = (int)(Common::getSettingValue('register_account') ?? 3);

        // STRICT POLICY: Count ALL accounts on this device (excluding current user)
        // Even logged out accounts are counted to prevent abuse
        $otherUsersCount = User::where('device_token', $deviceToken)
            ->where('device_token', '!=', '')
            ->whereNotNull('device_token')
            ->where('id', '!=', $userId)
            ->where('is_logout', 0)
            ->count();

        if ($otherUsersCount >= $register_account) {
            \Log::warning('Device account limit exceeded on login (STRICT)', [
                'device_token' => $deviceToken,
                'user_id' => $userId,
                'other_users_total_count' => $otherUsersCount,
                'limit' => $register_account,
                'policy' => 'strict - all accounts counted'
            ]);
            throw new CValidationException(__('max_accounts_reached'));
        }
    }
}
