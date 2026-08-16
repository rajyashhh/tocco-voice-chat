<?php

namespace App\Observers;

use App\Models\Pack;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Ware;
use App\Models\Follow;
use App\Models\BlackList;
use App\Models\FamilyUser;
use App\Models\Report_user;
use App\Models\AgencyJoinRequest;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function created(User $user)
    {
        $user->profile()->create([
            'gender' => 1
        ]);

        $data = [
            'user_id' => $user->id,
            'show_git' => 1,
            'show_intro' => 1,
            'show_banner' => 1,
            'show_invite_code' => 1,
        ];
        UserSetting::create($data);
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        $user->profile()->delete();
        AgencyJoinRequest::query()->where('user_id', $user->id)->delete();
        $user->ownerRoom()->delete();
        //        $user->carousels()->delete();
        //        Agency::where('owner_id',$user->id)->delete();
        FamilyUser::where('user_id', $user->id)->delete();
        Pack::where('user_id', $user->id)->delete();
        Report_user::where('user_id', $user->id)->delete();
        Follow::where('user_id', $user->id)->orWhere('followed_user_id', $user->id)->delete();
        BlackList::where('user_id', $user->id)->orWhere('from_uid', $user->id)->delete();
    }

    public function forceDeleting(User $user): void
    {
        $user->profile()->delete();
        AgencyJoinRequest::query()->where('user_id', $user->id)->delete();
        $user->ownerRoom()->delete();
        //        $user->carousels()->delete();
        //        Agency::where('owner_id',$user->id)->delete();
        FamilyUser::where('user_id', $user->id)->delete();
        Pack::where('user_id', $user->id)->delete();
        Report_user::where('user_id', $user->id)->delete();
        Follow::where('user_id', $user->id)->orWhere('followed_user_id', $user->id)->delete();
        BlackList::where('user_id', $user->id)->orWhere('from_uid', $user->id)->delete();
    }
    /**
     * @param User $user
     * @return void
     */
    public function creating(User $user)
    {
        $user->uuid = (string)rand(1000000, 9999999);
    }

    public function saving(User $user)
    {
        if ($user->phone) {
            $user->phone = str_replace([' ', '-', '/', '{', '}', '_', '(', ')'], '', $user->phone);
        }

        if (isset($user->oldDiValue)) {
            unset($user->oldDiValue);
            unset($user->oldDiamoundValue);
        }
        if (!$user->enableSaving) return;

        try {
            if ($user->agency_id) {
                if ($user->is_host == 0) {
                    $user->coins = 0;
                    uploadMonthlyDiamondReceive($user->id, 0);
                }
                $user->is_host = 1;
            }
            if ($user->agency_id == 0 || $user->agency_id == null) {
                if ($user->is_host == 1) {
                    //                    UserHandling::kickUserFromAgency($user);
                    $user->coins = 0;
                    //                    $user->monthly_diamond_received = 0;
                }
                $user->is_host = 0;
            }
            if ($user->status == 0) {
                $user->tokens()->delete();
            }

//            if ($user->isDirty('uuid')) {
//                do {
//                    // توليد قيمة uuid عشوائية
//                    $uuid = (string)rand(1000000, 9999999);
//                    $check_users = User::where("uuid", $uuid)->exists();
//                    $check_wares = Ware::where("value", $uuid)->exists();
//                } while ($check_users || $check_wares);
//
//                $user->uuid = $uuid;
//            }
        } catch (\Illuminate\Database\QueryException $e) {
        }
    }
}
