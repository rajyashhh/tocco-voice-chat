<?php

namespace App\Services;

use App\Helpers\Common;
use App\Helpers\WebPHelper;
use App\Http\Requests\Api\V1\Profile\ProfileRequest;
use App\Http\Resources\Api\V1\UserResource as V1UserResource;
use App\Http\Resources\Api\V1\UserVisitorResource;
use App\Models\Profile;
use App\Repositories\ProfileRepository;
use App\Repositories\User\UserRepository;
use App\Services\UserCounterServices;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Public\Http\Services\UserCounterServices as ServicesUserCounterServices;

class ProfileService
{
    protected $profileRepo;
    protected $userRepository;
    protected $profileRelationService;

    public function __construct(ProfileRepository $profileRepo, UserRepository $userRepository, ProfileRelationService $profileRelationService)
    {
        $this->profileRepo = $profileRepo;
        $this->userRepository = $userRepository;
        $this->profileRelationService = $profileRelationService;
    }

    public function updateProfile(ProfileRequest $request)
    {
        $data = [];

        $fields = ['name', 'phone', 'nickname', 'country_id', 'bio', 'chat_id', 'notification_id'];
        foreach ($fields as $field) {
            if ($request->has($field) && !empty($request->$field)) {
                $data[$field] = $request->$field;
            }
        }

        if ($request->email) {
            $data['email'] = $request->email;
        }
        if ($request->uuid) {
            $data['firebase_uuid'] = $request->uuid;
        }
        $user = $this->profileRepo->updateUser($request->user(), $data);

        $profileData = $request->only(['gender', 'birthday', 'province', 'city', 'country']);

        if ($profileData) $profile = $this->profileRepo->updateProfile($user->profile, $profileData, $user->id);
        $profile = Profile::where('user_id', $request->user()->id)->first();
        // if ($request->hasFile('image')) {

        //     $img = $request->file('image');
        //     $imageType = $img->getClientOriginalExtension();
        //     if ($imageType == 'gif' && !Common::hasInPack($user->id, 22, false)) {
        //         throw new \Exception(__('api_responses.gifImage'));
        //     }

        //     $user->profile_count += 1;
        //     $user->save();

        //     //  $newImagePath = WebPHelper::uploadWebp(
        //     //         $img,
        //     //         'profile',
        //     //         'profile_image'
        //     //     );
        //    // $newImagePass = Common::uploadProfileUser('profile', $img, $user->profile->id, $user->profile_count);
        //      $imagePath = Common::upload('profile', $img);
        //     $this->profileRepo->updateAvatar($profile, $imagePath);
        // }


        if ($request->hasFile('image')) {

            $img = $request->file('image');
            $extension = strtolower($img->getClientOriginalExtension());

            // GIF permission check
            if ($extension === 'gif' && !Common::hasInPack($user->id, 22, false)) {
                throw new \Exception(__('api_responses.gifImage'));
            }

            // Validate image before upload
            $validation = Common::validateMedia($img, 'profile');
            if (!$validation['valid']) {
                throw new \Exception($validation['error']);
            }

            $user->increment('profile_count');

            // Upload original + dispatch optimization job (async)
            $imagePath = Common::uploadOptimized(
                'profile',
                $img,
                'profile',
                Profile::class,
                $profile->id,
                'avatar'
            );

            $this->profileRepo->updateAvatar($profile, $imagePath);
        }

        if ($request->has('old_multi_image')) {
            $newImages =  explode(',', $request->old_multi_image);

            $existingImages = $user->images()->pluck('img')->toArray();

            $imagesToDelete = array_diff($existingImages, $newImages);

            foreach ($imagesToDelete as $image) {
                Storage::delete('profile/' . $image);
                $user->images()->where('img', $image)->delete();
            }
        }
        if ($request->hasFile('new_multi_image')) {
            foreach ($request->file('new_multi_image') as $file) {
                $newImagePath = WebPHelper::uploadWebp(
                    $file,
                    'profile',
                    'profile_image',
                    async: true  // Convert to WebP asynchronously
                );
                $user->images()->create([
                    'img' => $newImagePath,
                ]);
            }
        }



        $out = new V1UserResource($user);
        if ($profile->avatar === null) {
            $profile->avatar = $profile->gender == 1 ? "custom_image/male.png" : "custom_image/female.png";
            $this->profileRepo->updateAvatar($profile, $profile->avatar);
        }
        $lang = $user->lan ?? 'en';
        if ($profile->wasRecentlyCreated) {

            $title = __('api.welcome', ['name' => $user->name, 'app_name' => __(env('APP_NAME'), locale: $lang)], $lang);
            Common::sendOfficialMessage($user->id, $title, $user->name, titleAr: $title);
            (new ServicesUserCounterServices)->eventUser($user, 'official-messages');
        }
        return $out;
    }

    public function showProfile($me, $id)
    {
        (new ServicesUserCounterServices)->UpgradeDateForType($me, 'visitor');
        $user = $this->userRepository->findUserForProfile($id);

        if ($user && $me->id !== $user->id && !Common::checkPackPrev($me->id, 19)) {
            $this->userRepository->logProfileVisit($user, $me->id);
        }

        if ($user) {
            return Common::apiResponse(true, '', new V1UserResource($user), 200);
        }
        return Common::apiResponse(false, 'user not found', [], 404);
    }

    public function getProfileVisitorsList($user, $keyword = '')
    {
        (new ServicesUserCounterServices)->UpgradeDateForType($user, 'visitor');

        $profileVisitors = $this->profileRepo->getProfileVisits($user, $keyword);

        return  [$profileVisitors, ...$this->profileRelationService->getHelperArrays($user, $profileVisitors)];
    }

    public function getRelatedUsers($limit = 10)
    {
        return $this->profileRepo->getRandomUsers($limit);
    }

    public function getNearbyUsers($user)
    {
        $latitude = $user->lat;
        $longitude = $user->long;
        return $this->userRepository->users($user->id, $latitude, $longitude);
    }
}
