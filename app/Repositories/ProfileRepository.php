<?php

namespace App\Repositories;

use App\Models\Follow;
use App\Models\Profile;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use Str;

class ProfileRepository
{
    protected $profile;
    protected $user;

    public function __construct(Profile $profile, User $user)
    {
        $this->profile = $profile;
        $this->user = $user;
    }


    public function updateUser($user, $data)
    {
        $data['is_points_first'] = 0;
        $user->update($data);
        // $user->fill($data);
        // $user->is_points_first = 0;
        // $user->save();
        return $user;
    }


    function convertArabicNumbersToEnglish($dateString)
{
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return Str::replace($arabic, $english, $dateString);
}

    public function updateProfile($profile, $data, $userId )
    {
        if(!empty($data['birthday'])){
            $data['birthday']  = $this->convertArabicNumbersToEnglish($data['birthday']);
        }

        if($profile)
        {
            $profile->fill($data);
            $profile->save();
        }else{
            $profile = Profile::create([
             'gender' => @$data?->gender,
             'birthday' => @$data->birthday,
             'province' => @$data->province,
             'city' => @$data->city,
             'country' => @$data->country,
             'user_id'=>@$userId,
            ]);
        }

        return $profile;
    }

    public function updateAvatar($profile, $imagePath)
    {
        $profile->avatar = $imagePath;
        $profile->save();
        return $profile;
    }

    public function getProfileVisits(User $user, $keyword)
    {
        $blockedUserIds = array_unique(array_merge(
            $user->blockedUsers()->pluck('from_uid')->toArray(),
            $user->blockedMe()->pluck('user_id')->toArray()
        ));
        return $user->profileVisits()
        ->whereNotIn('visitor_id', $blockedUserIds)
        ->with([
            'room' => function ($query) {
                return $query->withoutAppends()->select(['id', 'room_pass', 'uid']);
            },
            'followPacks',
            'profile',
            'ware',
            'UserVip',
            'color_image',
            'followerByAuthUser'
        ])->fitterByUuid($keyword)->paginate(15);
    }

    public function getRandomUsers($limit = 10)
    {
        $user = User::find(auth()->user()->id);
        $user?->loadMissing('followeds');
        return User::has("images")
            ->with([
                'profile', 'UserVip', 'followPacks', 'ware', 'color_image', 'images',
                'followedByAuthUser', 'followerByAuthUser',
            ])
            ->whereNotIn("id", $user?->followeds?->pluck("followed_user_id")->toArray() ?? [])
            ->inRandomOrder()
            ->where("online", 1)
            ->limit($limit)
            ->get();
    }
}
