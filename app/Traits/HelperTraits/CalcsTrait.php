<?php

namespace App\Traits\HelperTraits;

use App\Helpers\Common;
use App\Models\Family;
use App\Models\FamilyLevel;
use App\Models\GiftLog;
use App\Models\OfficialMessage;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Models\UserLevelLog;
use Modules\Vip\Entities\UserVip;
use Modules\Vip\Entities\Vip;
use App\Models\Ware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

trait CalcsTrait
{



    public static function handelLevelLog($user_id = null, $type = null, $level = 0, $total = 0)
    {
        if ($type == 1) {
            $key = 'host';
        } elseif ($type == 2) {
            $key = 'normal';
        } else {
            $key = 'vip';
            return;
        }
        $level_log = UserLevelLog::query()
            ->where('user_id', $user_id)
            ->where('type', $type)
            ->where('level', $level)
            ->exists();
        if (!$level_log && $level != 0) {

            try {
                DB::beginTransaction();
                UserLevelLog::query()->create(
                    [
                        'user_id' => $user_id,
                        'type' => $type,
                        'level' => $level,
                        'total' => $total
                    ]
                );

                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
            }
        }
    }
    public static function getTotalGiftPrice($user_id)
    {
        return GiftLog::where(function ($query) use ($user_id) {
            $query->where('receiver_id', $user_id)
                ->orWhere('sender_id', $user_id);
        })->get(['receiver_id', 'sender_id', 'giftPrice']);
    }

    public static function getLevel($user_id = null, $type = null, $is_image = false, $giftLogs = null)
    {

        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) {
                return new stdClass();
            }
        } else {
            $user = $user_id;
        }

        if (!$giftLogs) {
            $giftLogs = self::getTotalGiftPrice($user_id);
        } else {
            $giftLogs = $giftLogs->where(function ($log) use ($user_id) {
                return $log->receiver_id == $user_id || $log->sender_id == $user_id;
            });
        }

        $star_num = $giftLogs->where('receiver_id', $user_id)->sum('giftPrice');
        $gold_num = $giftLogs->where('sender_id', $user_id)->sum('giftPrice');
        $vip_num  = $gold_num;


        $value = match ($type) {
            1 => $star_num,
            2 => $gold_num,
            3 => $vip_num,
            default => 0,
        };


        $total = $value;
        $exp   = $value * 1;
        $level = Vip::collectionBuilder()
            ->where('type', $type)
            ->where('exp', '<=', $exp)
            ->orderByDesc('exp')
            ->limit(1)
            ->value('level');


        if ($type == 1) {
            $level += @$user->sub_receiver_level;
        } elseif ($type == 2) {
            $level += @$user->sub_sender_level;
        }


        if ($is_image) {
            $img = '';
            if ($level >= 0) {
                $img = Vip::collectionBuilder()
                    ->where(['level' => $level, 'type' => $type])
                    ->value('img') ?? '';
            }
            return $img;
        } else {
            self::handelLevelLog($user_id, $type, $level, $total);
            return $level ?: 0;
        }
    }
    public static function getHzLevel($user_id, $is_img = false)
    {
        $vip_level = static::getLevel($user_id, 3);
        if (is_numeric($vip_level)) {
            $level = ceil($vip_level / 2);
        } else {
            $level = $vip_level;
        }

        if ($is_img) {
            $img = Vip::query()->where(['level' => $level, 'type' => 4])->value('img');
            return $img;
        } else {
            return $level ?: 0;
        }
    }

    public static function getCpLevel($cp_id)
    {
        $exp = DB::table('cp')->where(['id' => $cp_id])->value('exp');
        $where['type'] = 5;
        $where['exp'] = ['elt', $exp];
        $level = DB::table('vips')->where($where)->orderByRaw('id desc')->limit(1)->value('level');
        return $level ?: 0;
    }

    public static function room_hot($hot = null)
    {
        $hot = (int)$hot;
        if (! $hot) return 0;
        if ($hot <= 9999) {
            return $hot;
        } elseif ($hot > 9999 && $hot <= 99999999) {
            $hot = round($hot / 10000, 1);
            return $hot . 'w';
        } elseif ($hot > 99999999) {
            $hot = round($hot / 100000000, 2);
            return $hot . 'm';
        }
    }

    public static function getUserGifts($user_id)
    {

        $gifts = GiftLog::query()->where('receiver_id', $user_id)
            ->where('type', '2')
            ->with(
                'gifts',
                function ($q) {
                    $q->select('show_img,price');
                }
            )
            ->groupBy('giftId')
            ->orderBy('gifts.price');

        return $gifts;
    }


    //دخلي
    public static function user_income($user_id)
    {

        $days = array_map(
            function ($val) {
                return strtotime($val);
            },
            self::star_end_time(1)
        );

        $weeks     = array_map(
            function ($val) {
                return strtotime($val);
            },
            self::star_end_time(2)
        );
        $mons      = array_map(
            function ($val) {
                return strtotime($val);
            },
            self::star_end_time(3)
        );
        $last_mons = array_map(
            function ($val) {
                return strtotime($val);
            },
            self::star_end_time(4)
        );


        $arr['user_coins'] = DB::table('users')->where('id', $user_id)->value('coins');

        $arr['day_sum']      = DB::table('store_logs')->where('user_id', $user_id)->where('get_type', 21)
            ->where('created_at', 'between time', $days)
            ->sum('get_nums');
        $arr['week_sum']     = DB::table('store_logs')->where('user_id', $user_id)->where('get_type', 21)
            ->where('created_at', 'between time', $weeks)
            ->sum('get_nums');
        $arr['mon_sum']      = DB::table('store_logs')->where('user_id', $user_id)->where('get_type', 21)
            ->where('created_at', 'between time', $mons)
            ->sum('get_nums');
        $arr['last_mon_sum'] = DB::table('store_logs')->where('user_id', $user_id)->where('get_type', 21)
            ->where('created_at', 'between time', $last_mons)
            ->sum('get_nums');
        $arr                 = array_map(
            function ($val) {
                return bcadd($val, 0, 2);
            },
            $arr
        );
        $is_leader           = DB::table('users')->where('id', $user_id)->value('is_sign');
        $res['is_leader']    = $is_leader;

        if ($is_leader) {
            $room['room_coins']   = DB::table('users')->where('id', $user_id)->value('room_coins');
            $room['day_sum']      = DB::table('store_logs')->where('user_id', $user_id)
                ->whereIn('get_type', [31, 32])
                ->where('created_at', 'between time', $days)
                ->sum('get_nums');
            $room['week_sum']     = DB::table('store_logs')->where('user_id', $user_id)
                ->whereIn('get_type', [31, 32])
                ->where('created_at', 'between time', $weeks)
                ->sum('get_nums');
            $room['mon_sum']      = DB::table('store_logs')->where('user_id', $user_id)
                ->whereIn('get_type', [31, 32])
                ->where('created_at', 'between time', $mons)
                ->sum('get_nums');
            $room['last_mon_sum'] = DB::table('store_logs')->where('user_id', $user_id)
                ->whereIn('get_type', [31, 32])
                ->where('created_at', 'between time', $last_mons)
                ->sum('get_nums');
            $room                 = array_map(
                function ($val) {
                    return bcadd($val, 0, 2);
                },
                $room
            );
        } else {
            $room = (object)[];
        }
        $res['gift_income'] = $arr;
        $res['room_income'] = $room;
        return $res;
    }

    public static function vipByLevelAndType($level, $type)
    {
        return Vip::where('level', $level)->where('type', $type)->first();
    }

    //مركز الصف
    public static function level_center_old($user_id)
    {
        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];
        $user            = User::with(['senderLevel', 'receiverLevel'])->find($user_id);
        $diamondReceived = $user->total_received_diamonds;
        $receivedNum        =  floor($diamondReceived  * $expPercentages['exp_received_percentage']);
        $diamondSend             = $user->total_sender_diamonds;
        $senderNum        = floor($diamondSend  * $expPercentages['exp_sender_percentage']);

        $star_level      = $user->total_received_level;

        $firstVip_type1          = $user->receiverLevel;
        // self::vipByLevelAndType($star_level, 1);


        $star_level_img = !is_null($firstVip_type1) ? $firstVip_type1->img : '';



        $current_star_num       = self::getCurrentLevel(1, $star_level, 'exp');
        $next_star_num          = self::getNextLevel(1, $star_level, 'exp');
        $next_star_level        = self::getNextLevel(1, $star_level, 'level');


        $gold_level             = $user->total_sender_level;


        $firstVip_type2          = $user->senderLevel;
        //  self::vipByLevelAndType($gold_level, 2);
        $gold_level_img = !is_null($firstVip_type2) ? $firstVip_type2->img : '';

        $current_gold_num   = self::getCurrentLevel(2, $gold_level, 'exp');
        $next_gold_num   = self::getNextLevel(2, $gold_level, 'exp');
        $next_gold_level = self::getNextLevel(2, $gold_level, 'level');

        $data['receiver_num']        = (int)$receivedNum;
        $data['receiver_img']        = $star_level_img;
        $data['sender_num']          = (int)$senderNum;
        $data['sender_rem']          = floor((int)(($next_gold_num - $senderNum)));
        $data['receiver_rem']        = floor((int)(($next_star_num - $receivedNum)));
        $data['sender_img']          = $gold_level_img;

        $data['receiver_level']      = (int)$star_level;
        $data['next_receiver_num']   = (int)$next_star_num ?: 0;
        $data['next_receiver_level'] = (int)$next_star_level ?: 0;

        $data['sender_level']      = (int)$gold_level;
        $data['next_sender_num']   = (int)($next_gold_num);
        $data['next_sender_level'] = (int)$next_gold_level ?: 0;

        $data['prev_receiver_num'] = (int)$current_star_num ?: 0;
        $data['prev_sender_num'] = (int)($current_gold_num);
        $data['current_receiver_num'] = $current_star_num;
        $data['current_sender_num'] = $current_gold_num;

        $rt = (int)$next_star_num - (int)$current_star_num;
        $st = (int)$next_gold_num - (int)($current_gold_num);
        $rc = (int)$receivedNum - ((int)$current_star_num);
        $sc = (int)$senderNum - (int)($current_gold_num);

        $data['rt'] = $rt;
        $data['st'] = $st;
        $data['rc'] = $rc;
        $data['sc'] = $sc;


        if ($rt > 0 && ($rc / $rt) < 1 && ($rc / $rt) > 0) {
            $data['receiver_per'] = (float)($rc / $rt);
        } else {
            $data['receiver_per'] = (float)0.00;
        }

        if ($st > 0 && ($sc / $st) < 1 && ($sc / $st) > 0) {
            $data['sender_per'] = (float)($sc / $st);
        } else {
            $data['sender_per'] = (float)0.00;
        }

        return $data;
    }

    public static function getNextLevelData($type, $currentLevel)
    {
        $data = DB::table('vips')
            ->select('level', 'exp')
            ->where('type', $type)
            ->where(function ($query) use ($currentLevel) {
                $query->where('level', '>', $currentLevel)
                    ->orWhere(function ($query) {
                        $query->orderByDesc('exp')->limit(1);
                    });
            })
            ->orderBy('level')
            ->get();

        // استخراج القيمة المطلوبة
        $nextData = [
            'next_exp' => 0,
            'next_level' => 0,
        ];

        foreach ($data as $row) {
            if ($row->level > $currentLevel) {
                $nextData['next_exp'] = $row->exp;
                $nextData['next_level'] = $row->level;
                break;
            }
        }

        // تعيين القيم القصوى في حالة عدم وجود مستوى أعلى
        if ($nextData['next_exp'] == 0) {
            $nextData['next_exp'] = $data->last()->exp ?? 0;
        }

        if ($nextData['next_level'] == 0) {
            $nextData['next_level'] = $data->last()->level ?? 0;
        }

        return $nextData;
    }

    public static function getNextLevelDataFromCache($type, $currentLevel, $vipsData)
    {
        // تحقق من وجود البيانات في المصفوفة
        if (!isset($vipsData[$type])) return ['next_exp' => 0, 'next_level' => 0];

        $data = $vipsData[$type];
        $nextData = ['next_exp' => 0, 'next_level' => 0];

        // البحث عن المستوى التالي
        foreach ($data as $row) {
            if ($row->level > $currentLevel) {
                $nextData['next_exp'] = $row->exp;
                $nextData['next_level'] = $row->level;
                break;
            }
        }

        // تعيين القيم القصوى في حالة عدم وجود مستوى أعلى
        if ($nextData['next_exp'] == 0) {
            $nextData['next_exp'] = $data->last()->exp ?? 0;
        }

        if ($nextData['next_level'] == 0) {
            $nextData['next_level'] = $data->last()->level ?? 0;
        }

        return $nextData;
    }

    public static function getNextLevelDataFromCacheII($type, $currentLevel, $vipsData)
    {
        if (
            !$vipsData ||
            !isset($vipsData[$type]) ||
            !is_iterable($vipsData[$type])
        ) {
            return ['next_exp' => 0, 'next_level' => 0];
        }

        $data = $vipsData[$type];
        $nextData = ['next_exp' => 0, 'next_level' => 0];

        foreach ($data as $row) {
            if (!$row || !isset($row->level)) {
                continue;
            }

            if ($row->level > $currentLevel) {
                $nextData['next_exp']   = $row->exp ?? 0;
                $nextData['next_level'] = $row->level ?? 0;
                break;
            }
        }

        if ($nextData['next_exp'] == 0) {
            $last = $data->last();
            $nextData['next_exp']   = $last->exp ?? 0;
            $nextData['next_level'] = $last->level ?? 0;
        }

        return $nextData;
    }

    public static function getCurrentLevelFromCache($type = null, $level = 0, $field = null, $vipsData = [])
    {
        if (!$type || !$field || !isset($vipsData[$type])) return 0;

        // الحصول على بيانات المستوى من المصفوفة المجمعة
        $levelData = $vipsData[$type]->where('level', '=', $level)->first();

        return $levelData ? $levelData->$field : 0;
    }

    public static function level_center($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];
        // $user            = User::find($user_id);
        $diamondReceived = @$user->total_received_diamonds;
        $receivedNum        =  floor($diamondReceived  * $expPercentages['exp_received_percentage']);
        $diamondSend             = @$user->total_sender_diamonds;

        $senderNum        = floor($diamondSend  * $expPercentages['exp_sender_percentage']);

        $star_level      = @$user->total_received_level;

        $firstVip_type1          = $user->receiverLevel;
        // self::vipByLevelAndType($star_level, 1);

        $star_level_img = !is_null($firstVip_type1) ? $firstVip_type1->img : '';
        $vipsData = self::getVipsDataCached();

        $current_star_num = self::getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);

        $gold_level             = @$user->total_sender_level;

        $firstVip_type2          = $user->senderLevel;
        //self::vipByLevelAndType($gold_level, 2);
        $gold_level_img = !is_null($firstVip_type2) ? $firstVip_type2->img : '';

        // $current_gold_num   = self::getCurrentLevel(2, $gold_level, 'exp');
        // $next_gold_num   = self::getNextLevel(2, $gold_level, 'exp');
        // $next_gold_level = self::getNextLevel(2, $gold_level, 'level');
        $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);

        // استخدام الدالة للحصول على المستوى التالي من المصفوفة
        $nextStarData = self::getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $next_star_num = $nextStarData['next_exp'];
        $next_star_level = $nextStarData['next_level'];
        $next_gold_num = $nextGoldData['next_exp'];
        $next_gold_level = $nextGoldData['next_level'];

        $data['receiver_num']        = (int)$receivedNum;
        $data['receiver_img']        = $star_level_img;
        $data['sender_num']          = (int)$senderNum;
        $data['sender_rem']          = (($next_gold_num - $senderNum) < 0 ? 0 : floor((int)($next_gold_num - $senderNum)));
        $data['receiver_rem']        = (($next_star_num - $receivedNum) < 0 ? 0 : floor((int)($next_star_num - $receivedNum)));
        $data['sender_img']          = $gold_level_img;

        $data['receiver_level']      = (int)$star_level;
        $data['next_receiver_num']   = (int)$next_star_num ?: 0;
        $data['next_receiver_level'] = (int)$next_star_level ?: 0;

        $data['sender_level']      = (int)$gold_level;
        $data['next_sender_num']   = (int)($next_gold_num);
        $data['next_sender_level'] = (int)$next_gold_level ?: 0;

        $data['prev_receiver_num'] = (int)$current_star_num ?: 0;
        $data['prev_sender_num'] = (int)($current_gold_num);
        $data['current_receiver_num'] = $current_star_num;
        $data['current_sender_num'] = $current_gold_num;
        $data['exp-sender'] = $expPercentages['exp_sender_percentage'] ?? 1;
        $data['exp-receiver'] = $expPercentages['exp_received_percentage'] ?? 1;

        $rt = (int)$next_star_num - (int)$current_star_num;
        $st = (int)$next_gold_num - (int)($current_gold_num);
        $rc = (int)$receivedNum - ((int)$current_star_num);
        $sc = (int)$senderNum - (int)($current_gold_num);

        $data['rt'] = $rt < 0 ? 0 : $rt;
        $data['st'] = $st < 0 ? 0 : $st;
        $data['rc'] = $rc < 0 ? 0 : $rc;
        $data['sc'] = $sc < 0 ? 0 : $sc;

        if ($rt > 0 && ($rc / $rt) < 1 && ($rc / $rt) > 0) {
            $data['receiver_per'] = (float)($rc / $rt);
        } else {
            $data['receiver_per'] = (float)0.00;
        }
        if ($data['receiver_level'] == $data['next_receiver_level']) {
            $data['receiver_per'] = (float)1.00;
        }

        if ($st > 0 && ($sc / $st) < 1 && ($sc / $st) > 0) {
            $data['sender_per'] = (float)($sc / $st);
        } else {
            $data['sender_per'] = (float)0.00;
        }
        if ($data['sender_level'] == $data['next_sender_level']) {
            $data['sender_per'] = (float)1.00;
        }

        return $data;
    }

    /**
     * vips lookup table grouped by type, cached. The table is tiny and almost
     * static, so a short cache removes the per-row DB::table('vips')->get()
     * that level_center performs for every user in a list response.
     */
    public static function getVipsDataCached()
    {
        return Cache::remember('vips_grouped_by_type', now()->addMinutes(10), function () {
            return DB::table('vips')->orderBy('level')->get()->groupBy('type');
        });
    }

    /**
     * Same output contract as level_center(), but built for list responses:
     * it receives a pre-built (cached) vips collection and relies on the
     * eager-loaded receiverLevel/senderLevel relations instead of issuing a
     * vips query and two lazy relation queries per row.
     *
     * @param  \App\Models\User  $user  a fully loaded model (relations eager-loaded)
     * @param  \Illuminate\Support\Collection  $vipsData  vips grouped by type
     */
    public static function level_center_cached($user, $vipsData)
    {
        if (! $user instanceof User) {
            return new stdClass();
        }

        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];
        $diamondReceived = @$user->total_received_diamonds;
        $receivedNum     = floor($diamondReceived * $expPercentages['exp_received_percentage']);
        $diamondSend     = @$user->total_sender_diamonds;
        $senderNum       = floor($diamondSend * $expPercentages['exp_sender_percentage']);

        $star_level      = @$user->total_received_level;
        $firstVip_type1  = $user->receiverLevel;
        $star_level_img  = !is_null($firstVip_type1) ? $firstVip_type1->img : '';

        $current_star_num = self::getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);

        $gold_level      = @$user->total_sender_level;
        $firstVip_type2  = $user->senderLevel;
        $gold_level_img  = !is_null($firstVip_type2) ? $firstVip_type2->img : '';

        $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);

        $nextStarData = self::getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $next_star_num   = $nextStarData['next_exp'];
        $next_star_level = $nextStarData['next_level'];
        $next_gold_num   = $nextGoldData['next_exp'];
        $next_gold_level = $nextGoldData['next_level'];

        $data['receiver_num']        = (int)$receivedNum;
        $data['receiver_img']        = $star_level_img;
        $data['sender_num']          = (int)$senderNum;
        $data['sender_rem']          = (($next_gold_num - $senderNum) < 0 ? 0 : floor((int)($next_gold_num - $senderNum)));
        $data['receiver_rem']        = (($next_star_num - $receivedNum) < 0 ? 0 : floor((int)($next_star_num - $receivedNum)));
        $data['sender_img']          = $gold_level_img;

        $data['receiver_level']      = (int)$star_level;
        $data['next_receiver_num']   = (int)$next_star_num ?: 0;
        $data['next_receiver_level'] = (int)$next_star_level ?: 0;

        $data['sender_level']      = (int)$gold_level;
        $data['next_sender_num']   = (int)($next_gold_num);
        $data['next_sender_level'] = (int)$next_gold_level ?: 0;

        $data['prev_receiver_num'] = (int)$current_star_num ?: 0;
        $data['prev_sender_num'] = (int)($current_gold_num);
        $data['current_receiver_num'] = $current_star_num;
        $data['current_sender_num'] = $current_gold_num;
        $data['exp-sender'] = $expPercentages['exp_sender_percentage'] ?? 1;
        $data['exp-receiver'] = $expPercentages['exp_received_percentage'] ?? 1;

        $rt = (int)$next_star_num - (int)$current_star_num;
        $st = (int)$next_gold_num - (int)($current_gold_num);
        $rc = (int)$receivedNum - ((int)$current_star_num);
        $sc = (int)$senderNum - (int)($current_gold_num);

        $data['rt'] = $rt < 0 ? 0 : $rt;
        $data['st'] = $st < 0 ? 0 : $st;
        $data['rc'] = $rc < 0 ? 0 : $rc;
        $data['sc'] = $sc < 0 ? 0 : $sc;

        if ($rt > 0 && ($rc / $rt) < 1 && ($rc / $rt) > 0) {
            $data['receiver_per'] = (float)($rc / $rt);
        } else {
            $data['receiver_per'] = (float)0.00;
        }
        if ($data['receiver_level'] == $data['next_receiver_level']) {
            $data['receiver_per'] = (float)1.00;
        }

        if ($st > 0 && ($sc / $st) < 1 && ($sc / $st) > 0) {
            $data['sender_per'] = (float)($sc / $st);
        } else {
            $data['sender_per'] = (float)0.00;
        }
        if ($data['sender_level'] == $data['next_sender_level']) {
            $data['sender_per'] = (float)1.00;
        }

        return $data;
    }

    public static function level_center_v2($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];
        // $user            = User::find($user_id);
        $diamondReceived = $user->total_received_diamonds;
        $receivedNum        =  floor($diamondReceived  * $expPercentages['exp_received_percentage']);
        $diamondSend             = $user->total_sender_diamonds;

        $senderNum        = floor($diamondSend  * $expPercentages['exp_sender_percentage']);
        //$senderNum        = floor(2000000000000000000000  * $expPercentages[0]);

        $star_level      = $user->total_received_level;


        // $current_star_num       = self::getCurrentLevel(1, $star_level, 'exp');
        //        $vipsData = DB::table('vips')->get();
        $vipsData = Vip::collectionBuilder()->orderBy('level')->get();

        $firstVip_type1 = self::searchVipByLevelAndType($vipsData, $star_level, 1);
        //        $firstVip_type1          = self::vipByLevelAndType($star_level, 1);

        $star_level_img = !is_null($firstVip_type1) ? $firstVip_type1->img : '';


        $gold_level             = $user->total_sender_level;

        $firstVip_type2 = self::searchVipByLevelAndType($vipsData, $gold_level, 2);
        //        $firstVip_type2          = self::vipByLevelAndType($gold_level, 2);


        $vipsData = $vipsData->groupBy('type');
        //        Vip::where('level', $level)->where('type', $type)->first();

        // تعريف المتغيرات المطلوبة من المصفوفة المجمعة
        $current_star_num = self::getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);


        $gold_level_img = !is_null($firstVip_type2) ? $firstVip_type2->img : '';

        // $current_gold_num   = self::getCurrentLevel(2, $gold_level, 'exp');
        // $next_gold_num   = self::getNextLevel(2, $gold_level, 'exp');
        // $next_gold_level = self::getNextLevel(2, $gold_level, 'level');
        $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);

        // استخدام الدالة للحصول على المستوى التالي من المصفوفة
        $nextStarData = self::getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $next_star_num = $nextStarData['next_exp'];
        $next_star_level = $nextStarData['next_level'];
        $next_gold_num = $nextGoldData['next_exp'];
        $next_gold_level = $nextGoldData['next_level'];

        $data['receiver_num']        = (int)$receivedNum;
        $data['receiver_img']        = $star_level_img;
        $data['sender_num']          = (int)$senderNum;
        $data['sender_rem']          = (($next_gold_num - $senderNum) < 0 ? 0 : floor((int)($next_gold_num - $senderNum)));
        $data['receiver_rem']        = (($next_star_num - $receivedNum) < 0 ? 0 : floor((int)($next_star_num - $receivedNum)));
        $data['sender_img']          = $gold_level_img;

        $data['receiver_level']      = (int)$star_level;
        $data['next_receiver_num']   = (int)$next_star_num ?: 0;
        $data['next_receiver_level'] = (int)$next_star_level ?: 0;

        $data['sender_level']      = (int)$gold_level;
        $data['next_sender_num']   = (int)($next_gold_num);
        $data['next_sender_level'] = (int)$next_gold_level ?: 0;

        $data['prev_receiver_num'] = (int)$current_star_num ?: 0;
        $data['prev_sender_num'] = (int)($current_gold_num);
        $data['current_receiver_num'] = $current_star_num;
        $data['current_sender_num'] = $current_gold_num;
        $data['exp-sender'] = $expPercentages['exp_sender_percentage'] ?? 1;
        $data['exp-receiver'] = $expPercentages['exp_received_percentage'] ?? 1;

        $rt = (int)$next_star_num - (int)$current_star_num;
        $st = (int)$next_gold_num - (int)($current_gold_num);
        $rc = (int)$receivedNum - ((int)$current_star_num);
        $sc = (int)$senderNum - (int)($current_gold_num);

        $data['rt'] = $rt < 0 ? 0 : $rt;
        $data['st'] = $st < 0 ? 0 : $st;
        $data['rc'] = $rc < 0 ? 0 : $rc;
        $data['sc'] = $sc < 0 ? 0 : $sc;


        if ($rt > 0 && ($rc / $rt) < 1 && ($rc / $rt) > 0) {
            $data['receiver_per'] = (float)(($rc / $rt) * 100);
        } else {
            $data['receiver_per'] = (float)0.00;
        }

        if ($st > 0 && ($sc / $st) < 1 && ($sc / $st) > 0) {
            $data['sender_per'] = (float)(($sc / $st) * 100);
        } else {
            $data['sender_per'] = (float)0.00;
        }

        return $data;
    }

    public static function userLevelPer($user)
    {
        $gold_level             = $user->total_sender_level;
        $vipsData = Cache::remember('vips_by_type', 3600, function () {
            return DB::table('vips')->get()->groupBy('type');
        });

        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];

        $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $diamondSend             = $user->total_sender_diamonds;

        $senderNum        = floor($diamondSend  * $expPercentages['exp_sender_percentage']);

        $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);

        // If there's no next level or user is at max level, return 100%
        if (!isset($nextGoldData['next_exp']) || $nextGoldData['next_exp'] == 0) {
            return 1.0;
        }

        // Calculate the exp range for current level
        $sender_div = $nextGoldData['next_exp'] - $current_gold_num;

        // If next level exp equals current level exp (data issue), return 0 to avoid division by zero
        if ($sender_div <= 0) {
            return 0.0;
        }

        $data = min(1, max(0, ($senderNum - $current_gold_num) / $sender_div));
        return  $data;
    }


    public static function searchVipByLevelAndType($vips, $level, $type)
    {
        foreach ($vips as $vip) {
            if ($vip->level == $level && $vip->type == $type) {
                return $vip;
            }
        }
    }

    public static function level_center_ranking($user_id)
    {
        $user = User::query()->find($user_id)->first();
        $star_num = DB::table('gift_logs')->where('receiver_id', $user_id)->sum('giftPrice');
        $gold_num = DB::table('gift_logs')->where('sender_id', $user_id)->sum('giftPrice');


        //--------------------------------------
        $star_num += $user->sub_receiver_num ?: 0;
        $gold_num += $user->sub_sender_num ?: 0;
        //--------------------------------------

        $star_level      = self::getLevel($user_id, 1);


        $star_level_img      = self::getLevel($user_id, 1, true);
        $current_star_num = self::getCurrentLevel(1, $star_level, 'exp');
        $next_star_num   = self::getNextLevel(1, $star_level, 'exp');
        $next_star_level = self::getNextLevel(1, $star_level, 'level');

        $gold_level      = self::getLevel($user_id, 2);


        $gold_level_img      = self::getLevel($user_id, 2, true);
        $current_gold_num = self::getCurrentLevel(2, $gold_level, 'exp');
        $next_gold_num   = self::getNextLevel(2, $gold_level, 'exp');
        $next_gold_level = self::getNextLevel(2, $gold_level, 'level');

        // $data['receiver_num']        = (integer)$star_num;
        // $data['receiver_img']        = $star_level_img;
        // $data['sender_num']          = (integer)$gold_num;
        // $data['sender_rem']          = (integer)(($next_gold_num - $current_gold_num) -($gold_num - $current_gold_num));
        // $data['receiver_rem']        = (integer)(($next_star_num - $current_star_num) - ($star_num - $current_star_num));
        $data['sender_img']          = $gold_level_img;

        // $data['receiver_level']      = (integer)$star_level;
        // $data['next_receiver_num']   = (integer)$next_star_num?:0;
        // $data['next_receiver_level'] = (integer)$next_star_level?:0;

        // $data['sender_level']      = (integer)$gold_level;
        // $data['next_sender_num']   = (integer)($next_gold_num);
        // $data['next_sender_level'] = (integer)$next_gold_level?:0;

        // $data['prev_receiver_num'] = (integer)$current_star_num?:0;
        // $data['prev_sender_num'] = (integer)($current_gold_num);
        // $data['current_receiver_num'] = $current_star_num;
        // $data['current_sender_num'] = $current_gold_num;

        // $rt = $data['next_receiver_num']-$data['prev_receiver_num'];
        // $st = $data['next_sender_num']-$data['prev_sender_num'];
        // $rc = $data['receiver_num'] - $data['prev_receiver_num'];
        // $sc = $data['sender_num'] - $data['prev_sender_num'];
        // if ($rt > 0 && ($rc/$rt) < 1 && ($rc/$rt) > 0){
        //     $data['receiver_per'] = (double)($rc/$rt);
        // }else{
        //     $data['receiver_per'] = (double)0.00;
        // }
        // if($st > 0 && ($sc/$st) < 1 && ($sc/$st) > 0){
        //     $data['sender_per']=(double)($sc/$st);
        // }else{
        //     $data['sender_per']= (double)0.00;
        // }

        return $data;
    }


    public static function level_centerSerch($user_id)
    {
        $user = User::query()->find($user_id);
        $giftLogs = self::getTotalGiftPrice($user_id);
        $star_num = $giftLogs->where('receiver_id', $user_id)->sum('giftPrice');
        $gold_num = $giftLogs->where('sender_id', $user_id)->sum('giftPrice');

        //--------------------------------------
        $star_num += $user->sub_receiver_num ?: 0;
        $gold_num += $user->sub_sender_num ?: 0;
        //--------------------------------------

        $star_level      = self::getLevel($user_id, 1);


        $star_level_img      = self::getLevel($user_id, 1, true);
        $current_star_num = self::getCurrentLevel(1, $star_level, 'exp');
        $next_star_num   = self::getNextLevel(1, $star_level, 'exp');
        $next_star_level = self::getNextLevel(1, $star_level, 'level');

        $gold_level      = self::getLevel($user_id, 2);


        $gold_level_img      = self::getLevel($user_id, 2, true);
        $current_gold_num = self::getCurrentLevel(2, $gold_level, 'exp');
        $next_gold_num   = self::getNextLevel(2, $gold_level, 'exp');
        $next_gold_level = self::getNextLevel(2, $gold_level, 'level');

        // $data['receiver_num']        = (integer)$star_num;
        $data['receiver_img']        = $star_level_img;
        // $data['sender_num']          = (integer)$gold_num;
        // $data['sender_rem']          = (integer)(($next_gold_num - $current_gold_num) -($gold_num - $current_gold_num));
        // $data['receiver_rem']        = (integer)(($next_star_num - $current_star_num) - ($star_num - $current_star_num));
        $data['sender_img']          = $gold_level_img;

        $data['sender_level'] = @$user->total_sender_level;
        $data['reciver_level'] = @$user->total_received_level;

        // $data['receiver_level']      = (integer)$star_level;
        // // $data['next_receiver_num']   = (integer)$next_star_num?:0;
        // // $data['next_receiver_level'] = (integer)$next_star_level?:0;

        // $data['sender_level']      = (integer)$gold_level;
        // $data['next_sender_num']   = (integer)($next_gold_num);
        // $data['next_sender_level'] = (integer)$next_gold_level?:0;

        // $data['prev_receiver_num'] = (integer)$current_star_num?:0;
        // $data['prev_sender_num'] = (integer)($current_gold_num);
        // $data['current_receiver_num'] = $current_star_num;
        // $data['current_sender_num'] = $current_gold_num;

        // $rt = $data['next_receiver_num']-$data['prev_receiver_num'];
        // $st = $data['next_sender_num']-$data['prev_sender_num'];
        // $rc = $data['receiver_num'] - $data['prev_receiver_num'];
        // $sc = $data['sender_num'] - $data['prev_sender_num'];
        // if ($rt > 0 && ($rc/$rt) < 1 && ($rc/$rt) > 0){
        //     $data['receiver_per'] = (double)($rc/$rt);
        // }else{
        //     $data['receiver_per'] = (double)0.00;
        // }
        // if($st > 0 && ($sc/$st) < 1 && ($sc/$st) > 0){
        //     $data['sender_per']=(double)($sc/$st);
        // }else{
        //     $data['sender_per']= (double)0.00;
        // }

        return $data;
    }

    public static function level_center_search($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        $star_level = $user->total_received_level;

        $vipsData = Vip::collectionBuilder()->get();

        $firstVip_type1 = self::searchVipByLevelAndType($vipsData, $star_level, 1);

        $star_level_img = !is_null($firstVip_type1) ? $firstVip_type1->img : '';

        $gold_level             = $user->total_sender_level;

        $firstVip_type2 = self::searchVipByLevelAndType($vipsData, $gold_level, 2);

        $gold_level_img = !is_null($firstVip_type2) ? $firstVip_type2->img : '';

        $data['receiver_img']        = $star_level_img;
        $data['sender_img']          = $gold_level_img;

        return $data;
    }

    public static function level_centerSearchV2($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        $star_level_img      = $user->receiverLevel?->img;

        $gold_level_img      = $user->senderLevel?->img;

        $data['receiver_img']        = $star_level_img;
        $data['sender_img']          = $gold_level_img;

        $data['sender_level'] = @$user->total_sender_level;
        $data['reciver_level'] = @$user->total_received_level;

        return $data;
    }
    //مركز الأعضاء
    public static function vip_center($user_id, $level = null)
    {
        $vip_num        = DB::table('gift_logs')->where('sender_id', $user_id)->sum('giftPrice');
        $vip_level      = self::getLevel($user_id, 3);
        $next_vip_num   = self::getNextLevel(3, $vip_level, 'di');
        $next_vip_level = self::getNextLevel(3, $vip_level, 'level');

        $data['vip_num']        = (int)$vip_num ?: 0;
        $data['vip_level']      = (int)$vip_level ?: 0;
        $data['next_vip_num']   = (int)$next_vip_num ?: 0;
        $data['next_vip_level'] = (int)$next_vip_level ?: 0;


        $vip_auth = DB::table('vip_auth')->where(['type' => 3, 'enable' => 1]);
        if ($level) {
            $vip_auth = $vip_auth->where('level', $level);
        }
        $vip_auth = $vip_auth->get();
        foreach ($vip_auth as $k => &$v) {
            $v->is_on = ($vip_level >= $v->level) ? 1 : 0;
        }
        unset($v);
        $arr['my_data']     = $data;
        $arr['vip_prev'] = $vip_auth;
        return $arr;
    }

    public static function ovip_center($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        $uvip = $user->UserVip;
        if (!$uvip) return new stdClass();
        $vip = OVip::query()->find($uvip->vip_id);
        if (!$vip) return new stdClass();
        $vipIcon = Ware::where('level', $vip->level)->where('type', 10)->where('get_type', 1)->first();
        $hasColor = Common::hasInPack($user->id, 18, true);
        $color    = Common::hasColorInPack($user->id, 21, true);
        $vip_gifts = $vip->privilegs->contains(function ($priv) {
            return $priv->type == 14;
        });
        $vip_upload_gif = $vip->privilegs->contains(function ($priv) {
            return $priv->type == 22;
        });

        return [
            'id'        => 1,
            'level'     => $vip->level ?? 0,
            'name'      => $vip->name ?? '',
            'price'     => $vip->price ?? 0,
            'img_old'     => $vip->img ?? '',
            'img'     => $vipIcon->show_img ?? '',
            'image'     => $vip->image ?? '',
            'image_from_wares'     => $vipIcon->show_img ?? '',
            'expire'    => $vip->expire ?? 0,
            'ware_id' => $vipIcon?->id ?? 0,
            'color' =>  $color ?? '',
            'vip_gifts' => $vip_gifts ?? 0,
            'vip_upload_gif' => $vip_upload_gif ?? 0,
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip($user->id, 18, 'color') : null),
        ];
    }

    public static function ovip_center_v2($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        $vip = $user->UserVip?->OVip;
        if (!$vip) return new stdClass();

        $cacheKey = "ware_{$vip->level}_{10}";
        $vipIcon = Common::getCachedWares($cacheKey, $vip, 10);
        //        $vipIcon = Ware::where('level', $vip->level)->where('type', 10)->where('get_type', 1)->first();
        $hasColor = Common::hasInPackV2($user->packs, 18, true);
        return [
            'id'        => 1,
            'level'     => $vip->level ?? 0,
            'name'      => $vip->name ?? '',
            'price'     => $vip->price ?? 0,
            'img_old'     => $vip->img ?? '',
            'img'     => $vipIcon->show_img ?? '',
            'image'     => $vip->image ?? '',
            'image_from_wares'     => $vipIcon->show_img ?? '',
            'expire'    => $vip->expire ?? 0,
            'ware_id' => $vipIcon?->id ?? 0,
            'color' => '',
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVipV2($user, 18, 'color') : null),
        ];
    }

    public static function ovip_center_rank($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return 0;
        $uvip = $user?->UserVip;
        if (!$uvip) return new stdClass();

        $vip = OVip::query()->find($uvip->vip_id);

        if (!$vip) return new stdClass();
        $vipIcon = Ware::where('level', $vip->level)->where('type', 12)->where('get_type', 1)->first();

        return $vip->level;
        // [
        // 'id'        => 1,
        // 'level'     => $vip->level?? 0,
        // 'name'      => $vip->name?? '',
        // 'price'     => $vip->price ??0,
        // 'image'     => $vip->image??'',
        // 'image_from_wares'     => $vipIcon->show_img??'',
        // 'expire'    => $vip->expire??0
        // ];

    }

    public static function ovip_center_rank_v2($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user?->UserVip;
        if (!$uvip) return new stdClass();

        $vip = $uvip->OVip;

        if (!$vip) return new stdClass();

        return $vip->level;
    }


    public static function ovip_center_rank_img($user_id)
    {
        if (is_object($user_id)) {
            if (isset($user_id->userId)) {
                $user_id = $user_id->userId;
            } else {
                return '';
            }
        }
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return '';
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = OVip::query()->find($uvip->vip_id);

        if (!$vip) return '';
        $vipIcon = Ware::where('level', $vip->level)->where('type', 10)->where('get_type', 1)->first();

        // return $vip->level;
        // [
        return @$vipIcon->show_img ?? '';
        // [
        // 'id'        => 1,
        // 'level'     => $vip->level?? 0,
        // 'name'      => $vip->name?? '',
        // 'price'     => $vip->price ??0,
        // 'image'     => $vip->image??'',
        // 'image_from_wares'     => $vipIcon->show_img??'',
        // 'expire'    => $vip->expire??0
        // ];

    }

    public static function ovip_center_rank_img_v2($user_id)
    {
        if (is_object($user_id)) {
            if (isset($user_id->userId)) {
                $user = User::query()->find($user_id->userId);
            } elseif ($user_id instanceof User) {
                $user = $user_id;
            } elseif ($user_id instanceof JsonResource) {
                $user = $user_id->resource;
            } else {
                return '';
            }
        } elseif (is_numeric($user_id)) {
            $user = User::query()->find((int)$user_id);
        } else {
            return '';
        }

        if (!$user) return '';

        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = $uvip->OVip;

        if (!$vip) return '';

        $type = 10;

        $cacheKey = "ware_{$vip->level}_{$type}";

        $ware = Common::getCachedWares($cacheKey, $vip, $type);

        if (!$ware) return '';

        $value = optional($ware)->show_img;

        return ($value === 'NULL' || $value === null) ? '' : $value;
    }


    public static function ovip_center_room_admins($user)
    {
        if (is_int($user)) {
            $user = User::with(['UserVip.vip.privilegs'])->find($user);
            if (!$user) return new stdClass();
        }

        $uvip = $user->UserVip;
        if (!$uvip) return new stdClass();

        $vip = OVip::query()->find($uvip->vip_id);
        if (!$vip) return new stdClass();
        $vipIcon = $vip->wares->firstWhere('type', 10) ?? null; // preloaded relation
        $hasColor = $user->packs->contains(fn($p) => $p->type == 18 && $p->is_used);
        $color = $user->packs->firstWhere(fn($p) => $p->type == 21 && $p->is_used)?->ware->color ?? '';

        $vip_gifts = $vip->privilegs->contains('type', 14);
        $vip_upload_gif = $vip->privilegs->contains('type', 22);

        return [
            'id' => 1,
            'level' => $vip->level,
            'name' => $vip->name,
            'price' => $vip->price,
            'img_old' => $vip->img,
            'img' => $vipIcon?->show_img ?? '',
            'image' => $vip->image,
            'image_from_wares' => $vipIcon?->show_img ?? '',
            'expire' => $vip->expire,
            'ware_id' => $vipIcon?->id ?? 0,
            'color' => $color,
            'vip_gifts' => $vip_gifts,
            'vip_upload_gif' => $vip_upload_gif,
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip($user->id, 18, 'color') : null),
        ];
    }



    public static function wareUserVip($user_id, $type, $item, $isLatest = false)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return '';
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = OVip::query()->find($uvip->vip_id);

        if (!$vip) return  '';
        $ware = Ware::where('level', $vip->level)->where('type', $type)->where('get_type', 1)->first();
        $value = optional($ware)->{$item};
        return ($value === 'NULL' || $value === null) ? '' : $value;
    }


    public static function wareUserVipV2($user_id, $type, $item, $isLatest = false)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = $uvip->OVip;

        if (!$vip) return  '';
        $cacheKey = "ware_{$vip->level}_{$type}";

        $ware = Common::getCachedWares($cacheKey, $vip, $type);

        $value = optional($ware)->{$item};
        return ($value === 'NULL' || $value === null) ? '' : $value;
    }

    public static function wareUserVipColor($user_id, $type)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return '';
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return  '';
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = $uvip->OVip;

        if (!$vip) return '';
        $ware = Ware::where('level', $vip->level)->where('type', $type)->where('get_type', 1)->first();
        if (!$ware) return '';
        return @$ware?->color ?? '';
    }

    public static function wareUserVipColorV2($user_id, $type)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return '';
        } else {
            $user = $user_id;
        }
        if (!isset($user->UserVip)) return  '';
        $uvip = $user?->UserVip;
        if (!$uvip) return '';

        $vip = $uvip->OVip;

        if (!$vip) return '';
        $cacheKey = "ware_{$vip->level}_{$type}";

        $ware = Common::getCachedWares($cacheKey, $vip, $type);

        if (!$ware) return '';

        $value = optional($ware)->color;

        return ($value === 'NULL' || $value === null) ? '' : $value;
    }

    public static function ovip_centerforFaml($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        if (isset($user->UserVip)) return new stdClass();
        $uvip = $user->UserVip;
        if (!$uvip) return new stdClass();
        $vip = OVip::query()->find($uvip->vip_id);
        if (!$vip) return new stdClass();
        //        $p = $vip->privilegs;
        //        return $vip;
        return [
            'level'     => $vip->level,

        ];
    }

    //حقيبتي
    public static function my_store($user_id)
    {
        $user     = User::query()->find($user_id);
        $cou_list = Db::table('user_coupons')->where(['user_id' => $user_id, 'status' => 1])->get();
        foreach ($cou_list as $k => $va) {
            if ($va['expire'] <= time()) {
                Db::table('user_coupons')->where(['id' => $va['id']])->update(['status' => 3]);
            }
        }
        return [
            'id' => $user->id,
            'coins' => $user->di,
            'diamonds' => $user->coins,
            'silver_coins' => $user->gold,
            'usd' => (float)$user->sallary,
        ];
    }



    //my backpack  type

    //1 gem
    //2 gifts - not used
    //3 coupons
    //4 avatar frames
    //5 bubble boxes
    //6 entry effects
    //7 mic on the aperture
    //8 badges

    public function my_pack($user_id, $type)
    {
        if (!in_array($type, [1, 2, 3, 4, 5, 6, 7]))    return '';
        $where['a.user_id'] = $user_id;
        $where['a.type'] = $type;
        if ($type == 2) {
            $data = DB::table('pack')->alias('a')->join('gifts b', 'a.target_id = b.id')
                ->where($where)
                ->field("a.*,b.name,b.show_img,b.price")
                ->select();
        } else {
            $data = DB::table('pack')->alias('a')->join('wares b', 'a.target_id = b.id')
                ->where($where)
                ->field("a.*,b.name,b.show_img,b.title,b.color")
                ->select();
        }
        if (in_array($type, [4, 5, 6, 7])) {
            $dress_id = Db::table('users')->where(['id' => $user_id])->value("dress_" . $type);
        }
        foreach ($data as $k => &$v) {
            $v['show_img'] = $this->auth->setFilePath($v['show_img']);
            $v['is_dress'] = 0;
            if (in_array($type, [4, 5, 6, 7])) {
                $v['title'] = empty($v['expire']) ? "permanent" : date('Y-m-d H:i:s', $v['expire']) . "expire";
                $v['is_dress'] = $dress_id == $v['target_id']  ? 1 : 0;
                $v['color'] = $v['color'] ?: '';
            } elseif ($type == 2) {
                $v['title'] = "have" . $v['num'] . "value" . $v['num'] * $v['price'] . "diamond";
                $v['color'] = '';
            } else {
                $v['title'] = "have" . $v['num'] . "indivual " . $v['title'];
                $v['color'] = $v['color'] ?: '';
            }

            //status changed to read
            if ($v['is_read'] == 1) {
                Db::table('pack')->where(array('id' => $v['id']))->update(array('is_read' => 0));
            }
        }
        $this->ApiReturn(1, '', $data);
    }



    //المستوى الأقصى ونقاط الخبرة
    public static function getNextLevel($type = null, $level = 0, $field = null)
    {
        if (!$type || !$field) return 0;

        $max  = DB::table('vips')->where(['type' => $type])->orderByDesc('exp')->limit(1)->value($field);
        $next = DB::table('vips')->where('type', $type)->where('level', '>', $level)->orderBy('level')->limit(1)->value($field) ?: 0;
        return ($next ?: $max);
    }


    public static function getCurrentLevel($type = null, $level = 0, $field = null)
    {
        if (!$type || !$field) return 0;
        $le = DB::table('vips')->where('type', $type)->where('level', '=', $level)->limit(1)->value($field) ?: 0;
        return ($le);
    }
    //Get start and end time
    //1 today, 2 this week, 3 this month, 4 last month, 5 yesterday
    public static function star_end_time($type = 1, $class = 1)
    {
        $today = date('Y-m-d', time());
        if ($type == 1) {
            $star = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $end  = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y')));
        } elseif ($type == 2) {
            $w    = date('w', strtotime($today));
            $star = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - $w + 1, date('Y')));
            $end  = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d') + (7 - $w), date('Y')));
        } elseif ($type == 3) {
            $star            = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), str_pad(1, 2, 0, STR_PAD_LEFT), date('Y')));
            $last_month_days = date('t', strtotime(date('Y') . '-' . (date('m')) . '-' . str_pad(1, 2, 0, STR_PAD_LEFT)));
            $end             = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), $last_month_days, date('Y')));
        } elseif ($type == 4) {
            $star            = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') - 1, str_pad(1, 2, 0, STR_PAD_LEFT), date('Y')));
            $last_month_days = date('t', strtotime(date('Y') . '-' . (date('m') - 1) . '-' . str_pad(1, 2, 0, STR_PAD_LEFT)));
            $end             = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m') - 1, $last_month_days, date('Y')));
        } elseif ($type == 5) {
            $star = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
            $end  = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d') - 1, date('Y')));
        } else {
            return false;
        }
        if ($class == 1) {
            $arr[] = $star;
            $arr[] = $end;
        } elseif ($class == 2) {
            $arr[] = strtotime($star);
            $arr[] = strtotime($end);
        }
        return $arr;
    }


    public static function check_first_cp($user_id, $fromUid, $status)
    {
        $where = 'user_id|fromUid = ' . $user_id;
        $where .= ' and status = ' . $status;
        $id = DB::table('cps')->whereRaw($where)->whereRaw("`user_id` = ? OR `fromUid` = ?", [$fromUid, $fromUid])->value('id');
        return $id;
    }


    public static function getBrithdayMsg($birthday = null, $type = 1)
    {
        if (!$birthday)  return false;
        if (!in_array($type, [0, 1, 2]))   return false;
        [$year, $month, $day] = explode("-", $birthday);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff  = date("d") - $day;
        if ($month_diff < 0 || ($month_diff == 0 && $day_diff < 0)) {
            $year_diff--;
        }
        $info[] = ($year_diff < 0) ? 0 : $year_diff;

        $animals = array(
            'mouse',
            'Cattle',
            'Tiger',
            'rabbit',
            'dragon',
            'snake',
            'horse',
            'sheep',
            'monkey',
            'chicken',
            'dog',
            'pig'
        );
        $key = abs(($year - 1900) % 12);
        $info[] = $animals[$key];
        $signs = array(
            array('20' => 'Aquarius'),
            array('19' => 'Pisces'),
            array('21' => 'Aries'),
            array('20' => 'Taurus'),
            array('21' => 'Gemini'),
            array('22' => 'Cancer'),
            array('23' => 'Leo'),
            array('23' => 'Virgo'),
            array('23' => 'Libra'),
            array('24' => 'Scorpio'),
            array('22' => 'Sagittarius'),
            array('22' => 'Capricorn')
        );
        $arr = $signs[$month - 1];
        if ($day < key($arr)) {
            $arr = $signs[($month - 2 < 0) ? 11 : $month - 2];
        }
        $info[] = current($arr);
        return $info[$type];
    }


    public static function updateFamilyLevel($family_id)
    {
        $family = Family::query()->find($family_id);
        if ($family) {
            $family->inc(['today_rank' => 1, 'week_rank' => 1, 'month_rank' => 1]);
            $giftLogs = GiftLog::query()->where(function ($q) use ($family) {
                $q->where('receiver_family_id', $family->id)->orWhere('sender_family_id', $family->id);
            })->sum('giftPrice');
            $level = FamilyLevel::query()->where('exp', '<=', $giftLogs)->orderByDesc('exp')->first();
            if ($level) {
                if ($level->id != $family->current_level_id) {
                    DB::beginTransaction();
                    try {
                        OfficialMessage::query()->create(
                            [
                                'title' => 'family level upgraded',
                                'user_id' => $family->user_id,
                                'content' => 'congratulations your family level upgraded',
                                'url' => ''
                            ]
                        );
                        $family->update(['current_level_id' => $level->id]);
                        DB::commit();
                    } catch (\Exception $exception) {
                        DB::rollBack();
                    }
                }
            }
        }
    }


    public static function chargeLevel($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }
        //        $user = User::find($user_id);
        if (!$user) {
            return [
                'current_level'  =>  0,
                'current_exp'    =>  0,
                'current_img'    =>  '',
                'next_level'     =>  0,
                'next_exp'       =>  0,
                'next_img'       =>  '',
                'remaining'      =>  0,
                'progress'       =>  0,
            ];
        }
        $expLevel = $user->total_charge_coins + $user->sub_charger_coins;
        $currentLevel = Vip::collectionBuilder()->where("level", $user->charge_level)->where('type', 5)->orderByDesc('level')->first();
        if ($currentLevel) {
            $secondLevel = Vip::collectionBuilder()->where("type", 5)->where("level", ">", $currentLevel->level)->orderBy('id')->first();
        } else {
            $secondLevel = Vip::collectionBuilder()->where("type", 5)->orderBy('level')->first();
        }


        if ($secondLevel != null && $currentLevel != null) {
            $remaining = $secondLevel?->exp  - $user->total_charge_coins;
            $exactlyValue = @$secondLevel?->exp;
            $progressCurrent = $expLevel - $currentLevel->exp;
            $progressNext = $secondLevel->exp  - $currentLevel->exp;
            // $progress = $exactlyValue == 0 ? 1 : ($expLevel / $exactlyValue);
            $progress = $exactlyValue == 0 ? 1 : ($progressCurrent / $progressNext);
        } elseif ($currentLevel != null) {
            $remaining = $secondLevel?->exp == null ? 0 : $secondLevel?->exp - $user?->total_charge_coins;
            $exactlyValue = $secondLevel?->exp ?? 0;
            $progressCurrent = $expLevel - $currentLevel?->exp ?? 0;
            $progressNext = @$secondLevel?->exp - $currentLevel?->exp ?? 0;
            // $progress = $exactlyValue == 0 ? 1 : ($expLevel / $exactlyValue);
            $progress = $exactlyValue == 0 ? 1 : ($progressCurrent / $progressNext);
        } else {
            $progress = 1;
            $remaining = 0;
        }
        $chargeLevel = [
            'current_level'  => $currentLevel->level ?? 0,
            'current_exp'    => $currentLevel->exp ?? 0,
            'current_img'    => $currentLevel->img ?? '',
            'next_level'     => @$secondLevel->level ?? 0,
            'next_exp'       => @$secondLevel->exp ?? 0,
            'next_img'       => @$secondLevel->img ?? '',
            'remaining'         => @$remaining ?? 0,
            'progress'          => @$progress ?? 0,
        ];

        return $chargeLevel;
    }


    public static function level_center_room_admins(User $user)
    {
        static $cache = [];
        if (isset($cache[$user->id])) {
            return $cache[$user->id];
        }

        $expPercentages = config('exp_percentages', ['exp_received_percentage' => 1, 'exp_sender_percentage' => 1]);

        $vipsData = DB::table('vips')->get()->groupBy('type');

        $receivedNum = floor(($user->total_received_diamonds ?? 0) * ($expPercentages['exp_received_percentage'] ?? 1));
        $senderNum = floor(($user->total_sender_diamonds ?? 0) * ($expPercentages['exp_sender_percentage'] ?? 1));

        $star_level = $user->total_received_level ?? 0;
        $gold_level = $user->total_sender_level ?? 0;

        $current_star_num = self::getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);
        $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);
        $nextStarData = self::getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $receiver_diff = $nextStarData['next_exp'] - $current_star_num;
        $sender_diff = $nextGoldData['next_exp'] - $current_gold_num;
        $data = [
            'receiver_num' => $receivedNum,
            'sender_num' => $senderNum,
            'receiver_level' => $star_level,
            'sender_level' => $gold_level,
            'prev_receiver_num' => $current_star_num,
            'prev_sender_num' => $current_gold_num,
            'next_receiver_num' => $nextStarData['next_exp'] ?? 0,
            'next_receiver_level' => $nextStarData['next_level'] ?? 0,
            'next_sender_num' => $nextGoldData['next_exp'] ?? 0,
            'next_sender_level' => $nextGoldData['next_level'] ?? 0,
            'receiver_per' => ($receiver_diff > 0) ? max(0, min(1, ($receivedNum - $current_star_num) / $receiver_diff)) : 1,
            'sender_per' => ($sender_diff > 0) ? max(0, min(1, ($senderNum - $current_gold_num) / $sender_diff)) : 1,

            'exp-sender' => $expPercentages['exp_sender_percentage'] ?? 1,
            'exp-receiver' => $expPercentages['exp_received_percentage'] ?? 1,
        ];

        $cache[$user->id] = $data;

        return $data;
    }


    public static function level_center_my_data($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        $star_level = $user->total_received_level;
        $gold_level = $user->total_sender_level;

        $vipsData = Vip::collectionBuilder()->get();

        $firstVip_type1 = self::searchVipByLevelAndType($vipsData, $star_level, 1);
        $firstVip_type2 = self::searchVipByLevelAndType($vipsData, $gold_level, 2);

        return [
            'receiver_img' => !is_null($firstVip_type1) ? $firstVip_type1->img : '',
            'sender_img'   => !is_null($firstVip_type2) ? $firstVip_type2->img : '',
        ];
    }

    public static function ovip_center_my_data($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        $uvip = $user->UserVip;
        if (!$uvip) return new stdClass();

        $vip = OVip::query()->find($uvip->vip_id);
        if (!$vip) return new stdClass();

        $vipIcon = Ware::where('level', $vip->level)
            ->where('type', 10)
            ->where('get_type', 1)
            ->first();

        $hasColor = Common::hasInPack($user->id, 18, true);

        return [
            'vip_img'      => $vipIcon->show_img ?? $vip->img ?? $vip->image ?? '',
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVip($user->id, 18, 'color') : null),
        ];
    }

    public static function ovip_center_my_data_v2($user_id)
    {
        if (gettype($user_id) == 'integer') {
            $user = User::query()->find($user_id);
            if (!$user) return new stdClass();
        } else {
            $user = $user_id;
        }

        if (!isset($user->UserVip)) return new stdClass();
        $uvip = $user->UserVip;
        $vip  = $uvip->OVip;

        if (!$vip) {
            return new stdClass();
        }

        $cacheKey = "ware_icon_{$vip->level}_10";
        $vipIcon = self::getCachedWares($cacheKey, $vip, 10);

        $hasColor = Common::hasInPackV2($user->packs, 18, true);

        return [
            'vip_img'      => $vipIcon->show_img ?? $vip->img ?? $vip->image ?? '',
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVipV2($user, 18, 'color') : null),
        ];
    }
}
