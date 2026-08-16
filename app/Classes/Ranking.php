<?php

namespace App\Classes;
use App\helper\TimeHelper;
use App\Models\UserLuckyGift;
use Carbon\Carbon;
use App\Models\Code;
use App\Models\UserEarnInvitation;
use App\Models\Pack;
use App\Models\UserCodeInvitation;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use App\Models\Charge;
use App\Models\Family;
use App\Models\Config;
use App\Models\Follow;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\GiftLog;
use App\Http\Resources\Api\V1\MangerTypeResource;
class Ranking
{
    private $class;
    private $type;
    private $user;
    private $limit;
    private $room_uid;
    private $sent_to_owner;
    // function __construct($class, $type, $user, $limit = 30, $room_uid = null, $sent_to_owner = false)
    // {
    //     $this->class=$class;
    //     $this->type=$type;
    //     $this->user=$user;
    //     $this->limit=$limit;
    //     $this->room_uid=$room_uid;
    //     $this->sent_to_owner=$sent_to_owner;
    //         // initialize settingsFile ( string ) && initialize settings array variables
    // }
    public function rankingHand($class, $type, $user, $limit = 20, $room_uid = null, $sent_to_owner = false)
    {
        $user_id = $user->id;

        if (!$this->isValidClassAndType($class, $type)) {
            return Common::apiResponse(0, 'Parameter error', null, 422);
        }

        if ($class == 4){
            $query= UserLuckyGift::query();
            $this->applyDateFilters($query, $type);

            $data=$query->selectRaw("sum(total_win) as exp, user_id")
                ->groupBy('user_id')->orderByRaw("exp desc")
                ->limit($limit)->get()->reject(function ($q) {
                    return $q->exp == 0;
                });
            $this->transformData($data, $class,'user_id', 'user');
            return $this->prepareResponse($data, $user, $type, 'user_id', $user_id,$class,$limit);
        }
        [$keywords, $rel] = $this->getClassKeywordsAndRelation($class);

        $query = GiftLog::query()->whereHas($rel)
                                 ->when($class == 3, fn($q) => $q->with('roomOwner.ownerRoom:id,uid,room_name,room_cover'))
                                 ->when($class != 3, fn($q) => $q->with($rel));

        $this->applyDateFilters($query, $type);

        /*if (!$this->isClassOneOrTwo($class)) {
            $this->applyRoomFilters($query, $class, $room_uid, $sent_to_owner);

        }*/

//        if ($class == 3) {
//            $query->where('roomowner_id', $room_uid);
//        }

        $data = $this->getQueryResults($query, $keywords, $limit);
        $this->transformData($data, $class,$keywords, $rel);



        return $this->prepareResponse($data, $user, $type, $keywords, $user_id,$class,$limit);

    }

    private function isValidClassAndType($class, $type)
    {
        return in_array($class, [1, 2, 3,4]) && in_array($type, [0, 1, 2, 3, 4]);
    }

    private function getClassKeywordsAndRelation($class)
    {
        if ($class == 1) {
            return ['receiver_id', 'receiver'];
        } elseif ($class == 2) {
            return ['sender_id', 'sender'];
        }elseif ($class == 3) {
            return ['roomowner_id', 'roomOwner'];
        } else {
            return ['sender_id', 'sender'];
        }
    }

    private function applyDateFilters($query, $type)
    {
 
        $timezone = Common::timeZone();
        $now = Carbon::now($timezone);



        [$start, $end] = match ($type) {
            0 => [$now->copy()->startOfHour(), $now->copy()->endOfHour()],
            1 => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            2 => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            3 => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            default => [null, null],
        };

        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
    }

    private function isClassOneOrTwo($class)
    {
        return in_array($class, [1, 2]);
    }

    private function applyRoomFilters(&$query, $class, $room_uid, $sent_to_owner)
    {
        $query->where('roomowner_id', $room_uid);

    }

    private function getQueryResults($query, $keywords, $limit)
    {
        // Whitelist allowed column names to prevent SQL injection
        $allowedColumns = ['receiver_id', 'sender_id', 'roomowner_id', 'user_id'];
        if (!in_array($keywords, $allowedColumns, true)) {
            throw new \InvalidArgumentException('Invalid column name');
        }

        return $query->selectRaw("sum(giftPrice) as exp, " . $keywords)
            ->groupBy($keywords)->orderByRaw("exp desc")
            ->limit($limit)->get()->reject(function ($q) {
                return $q->exp == 0;
            });
    }

    private function transformData($data, $class,$keywords, $relation)
    {
        // foreach ($data as $v) {
        //     $users = User::query()->with('UserVip')->find($v->receiver_id);
        //     $this->transformUserData($v, $users, $user_id);
        // }

        $data = $data->reject(function ($q) {
            return $q->exp == 0;
        });

        $data = $data->values()->map(function ($item, $key) use ($data) {
            if ($key === 0) {
                $item->exp_diff = 0;
            } else {
                $item->exp_diff = $data[$key - 1]->exp - $item->exp  + 1;
            }
            return $item;
        });


        $data = $data->map(function ($v) use ($keywords, $class, $relation) {

            $user = $v->$relation;

            if ($user == null) {
                return null;
            }

            $v->user_id = $user->id;
            $value = $v->exp;
            $v->exp = numToString(ceil($v->exp));
            $v->exp_int = ceil($value);

            $value2 = $v->exp_diff;
            $v->remaining = numToString(ceil($v->exp_diff));
            $v->remaining_int = ceil($value2);

            $v->name = $class == 3? (@$user->ownerRoom?->room_name ?? '') : $user->name;
            $v->avatar = $class == 3? (@$user->ownerRoom?->room_cover ?? '') : $user->profile->avatar ;
            $v->frame = Common::getUserDress($user->id, $user->dress_1, 4, 'img2', true) ?: Common::getUserDress($user->id, $user->dress_1, 4, 'img1', true);
            $v->frame_id = $user->dress_1;
            $v->type_user =  intval(@$user->type_user) ?: 0;
            $v->manger_type =  !$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);

//            $senderimg      = Common::level_center(@$v->user_id);
//            $reseverimg     = Common::level_center(@$v->user_id);
//            $v->sender_img  = $senderimg['sender_img'] ?? '';
//            $v->resever_img = $reseverimg['receiver_img'] ?? '';
            $v->vip_level = @$user->UserVip->level ?? 0;
            $v->sender_level = @$user->total_sender_level;
            $v->reciver_level = @$user->total_received_level;
            $v->country = @$user->country;
            unset($v->$relation);
            return $v;
        })->reject(function ($v) {
            return $v == null;
        });

        /*$i = $l = 0;
        $data->each(function ($v) use (&$i, &$l, $keywords, $user_id) {
            if ($v->{$keywords} == $user_id) {
                $l = ++$i;
            }


        });*/
    }

    private function transformUserData($v, $users, $user_id)
    {
        $v->user_id = $v->receiver_id;
        $v->exp = numToString(ceil($v->exp));

        if ($users) {
            $v->name = $users->name ?: '';
            $v->avatar = $users->profile->avatar ?: '';
        } else {
            $v->name = '';
            $v->avatar = '';
        }

        unset($v->receiver_id);
    }

    private function prepareResponse($data, User $user, $type, $keywords, $user_id,$class,$limit)
    {
        $kong['user_id']    = 0;
        $kong['uuid']       = '';
        $kong['exp']        = '0';
        $kong['exp_int']        = 0;
        $kong['remaining']        = '0';
        $kong['remaining_int']        = 0;
        $kong['name']       = '';
        $kong['avatar']     = '';
        $kong['frame']      = '';
        $kong['frame_id']   = 0;
        $kong['sender_img'] = '';
        $kong['reseverimg'] = '';
        $kong['vip_level'] = 0;
        $kong['sender_level'] = 0;
        $kong['reciver_level'] = 0;
        $kong['type_user'] = 0;
        $kong['manger_type'] = null;

        $data[0] = isset($data[0]) ? $data[0] : $kong;
        $data[1] = isset($data[1]) ? $data[1] : $kong;
        $data[2] = isset($data[2]) ? $data[2] : $kong;
//        if ($limit == 3) return $data;


        $user->sort = $this->getUserSortValue($data, $user_id);
        $user->user_id = $user->id;

        $arr['user'] = $user->only('user_id', 'uuid', 'exp', 'name', 'avatar', 'frame', 'frame_id','manger_type_id');

        $sender_img = @$user->getImageReceiverOrSender('sender_id', 2)?->img ?? '';
        $vip_level  = Common::ovip_center_rank($arr['user']['user_id']);


        if (gettype($vip_level) != 'integer') {
            $vip_level = 0;
        }
        $arr['user']['exp'] = $arr['user']['exp'] ?? '0';
        $arr['user']['sender_img'] = $sender_img;
        $arr['user']['vip_level']  = $vip_level;
        $arr['user']['sender_level']  = $user->total_sender_level;
        $arr['user']['reciver_level']  = $user->total_received_level;
        $arr['user']['type_user'] =  intval(@$user->type_user) ?: 0;
        $arr['user']['country'] =  @$user->country;
        $arr['user']['manger_type'] =!$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);


        $toArray = $data->toArray();
        $countData = count($data);
        $arr['top'] = $countData < 4 ? $data : array_slice($toArray, 0, 3);
        $arr['other'] = $countData < 4 ? [] : array_slice($toArray, 3);
        return $arr;
    }

    private function prepareResponse2($data, $user, $type, $keywords, $user_id,$class,$limit)
    {
        $kong['user_id']    = 0;
        $kong['uuid']       = '';
        $kong['exp']        = '0';
        $kong['name']       = '';
        $kong['avatar']     = '';
        $kong['frame']      = '';
        $kong['frame_id']   = 0;
        $kong['sender_img'] = '';
        $kong['reseverimg'] = '';
        $kong['vip_level'] = 0;
        $kong['sender_level'] = 0;
        $kong['reciver_level'] = 0;
        $kong['type_user'] = 0;
        $kong['manger_type'] = null;


        $user->user_id = $user->id;

        $arr['user'] = $user->only('user_id', 'uuid', 'exp', 'name', 'avatar', 'frame', 'frame_id','manger_type_id');

        $sender_img = @$user->getImageReceiverOrSender('sender_id', 2)?->img ?? '';
        $vip_level  = Common::ovip_center_rank($arr['user']['user_id']);


        if (gettype($vip_level) != 'integer') {
            $vip_level = 0;
        }
        $arr['user']['exp'] = $arr['user']['exp'] ?? '0';
        $arr['user']['sender_img'] = $sender_img;
        $arr['user']['vip_level']  = $vip_level;
        $arr['user']['sender_level']  = $user->total_sender_level;
        $arr['user']['reciver_level']  = $user->total_received_level;
        $arr['user']['type_user'] =  intval(@$user->type_user) ?: 0;
        $arr['user']['manger_type'] =!$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);


        $toArray = $data->toArray();
        $countData = count($data);
        $arr['top'] = $countData < 4 ? $data : array_slice($toArray, 0, 3);
        $arr['other'] = $countData < 4 ? [] : array_slice($toArray, 3);
        return $arr;
    }

    private function getUserSortValue($data, $user_id)
    {
        $sort = 0;
        foreach ($data as $i => $v) {
            if (isset($v->receiver_id) && $v->receiver_id == $user_id) {
                $sort = $i + 1;
                break;
            }
        }
        return $sort ? (string) $sort : '99+';
    }


}
