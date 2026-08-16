<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use App\Models\User;
use App\Models\BoxUse;
use App\Jobs\OpenBoxJob;
use App\Models\PickBoxList;
use App\Models\RoomVisitor;
use App\Models\UserBoxGift;
use App\Facades\RedisService;
use Illuminate\Console\Command;
use App\Http\Services\LuckyBoxServices;


class SuperLuckyBoxCommend extends Command
{
    protected $signature = 'super-lucy-box';

    protected $description = 'Command description';

    public function handle()
    {
        $cacheKey = 'timezone';
        $timezone = \Cache::rememberForever($cacheKey, function () {
            $setting = \App\Models\Setting::where('key', 'timezone')->first();
            return $setting?->value ?? 'UTC';
        });
        $timestamp = Carbon::now( $timezone)->timestamp;

        $userBoxes =   BoxUse::where('end_at', '<', $timestamp)->where('type', 1)->where('is_closed', false)->get();
        if (!$userBoxes) return '';
        foreach ($userBoxes as $userBox) {
            $keyBoxUse  = 'BoxUse_' . $userBox->bid;
            $pickerBoxIds =   PickBoxList::where('box_user_id', $userBox->id)->pluck('user_id')->toArray();
            if (!$pickerBoxIds) return '';
            $users =  User::whereIn('id', $pickerBoxIds)->inRandomOrder()->get();
            foreach ($users as $user) {

                $userInRoom =     RoomVisitor::where('user_id', $user->id)->exists();
                if (!$userInRoom) return '';
                
                if ($userBox->users_num != $userBox->used_num) {

                    if (($userBox->not_used_num == 1)) {
                        $userBox->not_used_num = 0;
                        $fin = 1;
                    } else {
                        $fin = 0;
                        $userBox->not_used_num -= 1;
                    }

                    $giftService = new LuckyBoxServices();
                    $coins = $giftService->getCoins($fin, $userBox->unused_coins, $userBox->not_used_num);

                    //            put user in redis
                    $data = [
                        'box_uses_id' => $userBox->id,
                        'user_id' => $user->id,
                        'coins' => $coins,
                        'room_uid' => $userBox->room_uid,
                        'room_id' => $userBox->room_id,
                        'type' => $userBox->type,
                        'box_uses_owner_id' => $userBox->user_id,
                        'image' => $userBox->image,
                        'label' => $userBox->label
                    ];

                    if (UserBoxGift::where(['user_id' => $user->id, 'box_uses_id' => true])->exists()) {
                        return '';
                    }

                    UserBoxGift::query()->create($data);

                    $userBox['used_coins'] += $coins;
                    $userBox['used_num'] += 1;
                    $userBox['unused_coins'] -= $coins;
                    //update box use in redis
                    RedisService::updateUnSerialize($keyBoxUse, $userBox);
                    dispatch(new OpenBoxJob($userBox->bid, $user->id, $user->name))->onQueue('luckyBox');

                    $user->increment('di', $coins);
                }
                User::where('id', $userBox->user_id)->increment('di', $userBox->unused_coins);
                $userBox->is_closed = true;
                $userBox->save();
            }
        }
    }
}
