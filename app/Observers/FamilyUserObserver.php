<?php

namespace App\Observers;

use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;

class FamilyUserObserver
{
    /**
     * Handle the FamilyUser "created" event.
     *
     * @return void
     */
    public function created(FamilyUser $familyUser)
    {
        if ($familyUser->status == 1) {
            User::query()->where('id', $familyUser->user_id)->update(['family_id' => $familyUser->family_id]);
        }
    }

    /**
     * Handle the FamilyUser "updated" event.
     *
     * @return void
     */
    public function updated(FamilyUser $familyUser)
    {
        if ($familyUser->status == 1) {
            User::query()->where('id', $familyUser->user_id)->update(['family_id' => $familyUser->family_id]);
        }
    }

    /**
     * Handle the FamilyUser "deleted" event.
     *
     * @return void
     */
    public function deleted(FamilyUser $familyUser)
    {
        $family = Family::query()->where('id', $familyUser->family_id)->first();
        if ($family) {
            User::query()->where('id', $familyUser->user_id)->where('id', '!=', $family->user_id)->update(['family_id' => 0]);
        } else {
            User::query()->where('id', $familyUser->user_id)->update(['family_id' => 0]);
        }
    }
}
