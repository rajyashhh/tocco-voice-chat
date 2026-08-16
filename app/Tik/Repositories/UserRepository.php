<?php

namespace App\Tik\Repositories;

use App\Models\Room;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Jobs\SendMessageToAllUsers;
use GuzzleHttp\Client;


/** @property User $model*/
class UserRepository extends AbstractRepository
{


    public function __construct()
    {
        parent::__construct(new User());
    }

    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }


    public function searchUser($userUuId)
    {
        return $this->model->searchByUuid($userUuId)->first();
    }

    public function filterUser($userUuId)
    {
        return $this->model->select('*')->selectRaw("((LENGTH(users.uuid) - LENGTH(REPLACE(users.uuid, ?, ''))) / CHAR_LENGTH(users.uuid)) * 100 AS matching_percentage", [$userUuId])->fitterByUuid($userUuId)->get();
    }
    public function filterUserNew($userUuId)
    {
        $prefix = $userUuId . '%';

        // Full columns: the User model appends accessors (total_received_diamonds,
        // total_sender_diamonds, total_*_level) computed from raw columns, so a
        // narrow column list would (a) error on accessor names that are not real
        // columns and (b) starve the accessors of their source columns.
        return $this->model
            ->select('*')
            ->with([
                'profile:id,user_id,avatar',
                'color_image',
                'specialId.ware:id,value,show_img',
                'receiverLevel:level,type,img',
                'senderLevel:level,type,img',
                'UserVip',
                'packs',
            ])
            ->where(function ($query) use ($prefix) {
                $query->where('uuid', 'like', $prefix)
                    ->orWhere('special_id', 'like', $prefix);
            })
            ->orderBy('uuid')
            ->limit(30)
            ->get();
    }

    public function searchUserById($userUuId)
    {
        return $this->model->find($userUuId);
    }

    public function searchUserByUUId($userUuId)
    {
        return $this->model->SearchByUuid($userUuId)->first();
    }


    public function incrementCoins($userUuIdOrId, $coins)
    {
        $user = $this->searchUser($userUuIdOrId) ?? $this->findById($userUuIdOrId);
        $this->incrementUserCoins($user, $coins);
        return true;
    }

    public function decrementCoins($userUuIdOrId, $coins)
    {
        $user = $this->searchUser($userUuIdOrId) ?? $this->findById($userUuIdOrId);
        $this->decrementUserCoins($user, $coins);
        return true;
    }

    public function incrementUserCoins($user, $coins)
    {

        $user->increment('di', $coins);
        return true;
    }

    public function decrementUserCoins($user, $coins)
    {
        $user->decrement('di', $coins);
        return true;
    }

    public function incrementUnreadMessage($user)
    {
        $user->increment('unread_count_message');
        return true;
    }

    public function findById($id)
    {
        return $this->model->with('profile')->find($id);
    }

    public function updateUnreadCountMessage($user)
    {
        // Targeted single-column write on a GET list fetch: zero the unread
        // counter without a full-row UPDATE and without firing User model
        // events/observers (the old $user->save() ran saving/updating/updated +
        // UserObserver every poll). The only field changed here is not part of
        // the data_user_/user_rooms_ caches the `updated` event forgets, so
        // skipping those events leaves no stale read. Mirror the in-memory model
        // so any later use of $user in the same request reflects the zeroing.
        User::whereKey($user->id)->update(['unread_count_message' => 0]);
        $user->unread_count_message = 0;
    }

    public function updateUser($user)
    {
        $user->save();
    }

    public function getUsers($ids)
    {
        return $this->model->query()
            ->with([
                'agency:id,owner_id,name,type',
                'profile:id,user_id,avatar,birthday,gender',
                'family:id,user_id,name,total_diamond,current_level_id',
                'currentMonthlyDiamond',
                'UserVip',
                'mangerType',
                'specialId.ware',
            ])
            ->whereIn('id', $ids)->get();
    }

    public function getAdmins($ids)
    {
        if (empty($ids))
            return collect();

        //        $vipsData = DB::table('vips')->get()->groupBy('type');
        //        $expPercentages = config('exp_percentages', [
        //            'exp_received_percentage' => 1,
        //            'exp_sender_percentage' => 1
        //        ]);

        $admins = $this->model->select([
            'id',
            'name',
            'uuid',
            'dress_1',
            'color_id',
            'image_color_id',
            'sender_level',
            'received_level'
        ])->with([
                    //            'agency.owner',
                    //            'agency.mempers',
                    'profile:id,user_id,avatar',
                    //            'family',
                    'packs:id,user_id,is_used,type,target_id',
                    'eligiblePacks',
                    //            'ownAgency',
                    //            'userSetting',
                    //            'activePack20'
                    'specialId.ware',
                ])->whereIn('id', $ids)->get();

        $admins->each(function ($user) {
            $user->user_types2 = $this->computeUserTypes($user);
            //            $user->preloaded_uuid = $this->computeUuid($user);
            //            $user->preloaded_level = $this->computeLevel($user, $vipsData, $expPercentages);
        });

        return $admins;
    }

    private function computeUserTypes($user): array
    {
        $types = [];
        if ($user->type_user >= 1)
            $types[] = 1;
        if ($user->type_user >= 2)
            $types[] = 2;
        if ($user->agency?->type === 'shipping')
            $types[] = 3;
        if ($user->is_bd)
            $types[] = 4;

        return empty($types) ? [0] : array_unique($types);
    }

    private function computeUuid($user)
    {
        $pack = $user->packs
            ->where('type', 25)
            ->where('is_used', true)
            ->first(fn($p) => $p->ware?->value == $user->special_id);

        return ($user->special_id && $pack) ? $user->special_id : $user->original_uuid;
    }

    private function computeLevel($user, $vipsData, $expPercentages): array
    {
        $diamondReceived = $user->total_received_diamonds ?? 0;
        $diamondSend = $user->total_sender_diamonds ?? 0;
        $star_level = $user->total_received_level ?? 0;
        $gold_level = $user->total_sender_level ?? 0;

        $current_star_num = Common::getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);
        $current_gold_num = Common::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);
        $nextStarData = Common::getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = Common::getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $receivedNum = floor($diamondReceived * ($expPercentages['exp_received_percentage'] ?? 1));
        $senderNum = floor($diamondSend * ($expPercentages['exp_sender_percentage'] ?? 1));

        $receiver_div = max(1, ($nextStarData['next_exp'] ?? 1) - $current_star_num);
        $sender_div = max(1, ($nextGoldData['next_exp'] ?? 1) - $current_gold_num);

        return [
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
            'receiver_per' => min(1, max(0, ($receivedNum - $current_star_num) / $receiver_div)),
            'sender_per' => min(1, max(0, ($senderNum - $current_gold_num) / $sender_div)),
            'exp-sender' => $expPercentages['exp_sender_percentage'] ?? 1,
            'exp-receiver' => $expPercentages['exp_received_percentage'] ?? 1,
        ];
    }




    public function getUsersWithPaginate($ids, $paginate)
    {
        return $this->model->query()
            ->with([
                'profile:id,user_id,avatar,birthday,gender',
                'UserVip',
                'mangerType',
                'specialId.ware',
            ])
            ->whereIn('id', $ids)->paginate($paginate);
    }


    public function usersRoom($roomAdminActive)
    {
        return $this->model->withoutAppends()->select([
            '*',
            DB::raw('(SELECT  level from users_vips
                     where (users_vips.expire >= UNIX_TIMESTAMP()) and users_vips.user_id = users.id order by level  desc limit 1) as max_level
                     ')
        ])->with([
                    'packs' => function ($query) {
                        return $query->whereIn('type', [4, 18, 5, 17]);
                    },
                    'profile',
                    'UserVip',
                    'dress1',
                ])->where(function ($query) {
                    $query->whereDoesntHave('packs')->orWhereHas('packs', function ($q) {
                        $q->where("type", '!=', 17)->orWhere(fn($q) => $q->where('type', 17)->where("is_used", 0));
                    });
                })->whereIn('users.id', $roomAdminActive)->orderByDesc(DB::raw('max_level'));
    }

    public function anotherUserRoom($roomVisitorArray, $limit, $offset)
    {
        return $this->usersRoom($roomVisitorArray)->limit($limit)->offset($offset)->get();
    }

    public function updateFamilyId($user, $familyId)
    {
        $user->update(['family_id' => $familyId]);
        return true;
    }

    public function updateUsersFamily($familyId, $newFamilyId)
    {
        $this->model->where('family_id', $familyId)->update(['family_id' => $familyId]);
        return true;
    }

    public function findByPhoneUser($phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findByPhoneUserTrashed($phone)
    {
        return $this->model->withTrashed()->where('phone', $phone)->first();
    }

    public function updateDeviceToken($user, $deviceToken)
    {
        $user->device_token = $deviceToken;
        $this->updateUser($user);
        return true;
    }

    // public function updateIsLogout($user, $isLogout, $is_new = false)
    // {
    //     $user->lan = app()->getLocale() ?? 'en';
    //     $user->is_logout = $isLogout;
    //     if ($is_new) {
    //         $user->is_points_first = true;
    //     } else {
    //         $user->is_points_first = false;
    //     }
    //     $notification_id = @request()->notification_id;
    //     if ($notification_id) {
    //         $user->notification_id = $notification_id;
    //     }
    //     $this->updateUser($user);
    // }
    public function updateIsLogout($user, $isLogout, $is_new = false)
    {
        if (!$user)
            return;

        $user->lan = app()->getLocale() ?? 'en';
        $user->is_logout = (bool) $isLogout;
        $user->is_points_first = (bool) $is_new;

        $notification_id = request()->input('notification_id');
        if ($notification_id) {
            $user->notification_id = $notification_id;
        }

        try {
            $this->updateUser($user);
        } catch (\Exception $e) {
            logger()->error('Failed to update user in updateIsLogout', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function findByGoogleId($googleId)
    {
        return $this->model->whereNotNull('google_id')->where('google_id', $googleId)->first();
    }



    public function checkTrashedEmail($email, $googleId)
    {
        return $this->model->withTrashed()->where(fn($q) => $q->whereNotNull('email')->where('email', $email))->orWhere('google_id', $googleId)->first();
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByAppleId($appleId)
    {
        return $this->model->where('apple_id', $appleId)->first();
    }

    public function findByHuawei($huaweiId)
    {
        return $this->model->query()->whereNotNull('huawei_id')->where('huawei_id', $huaweiId)->first();
    }

    public function checkEmail($email)
    {
        return $this->model->query()->whereNotNull('email')->where('email', $email)->exists();
    }

    public function findByTrashedEmail($email, $googleId)
    {
        return $this->model->onlyTrashed()->where(fn($q) => $q->whereNotNull('email')->where('email', $email))->orWhere('google_id', $googleId)->first();
    }

    public function checkByGoogleId($userId, $googleId)
    {
        return $this->model->query()->where('google_id', $googleId)->where('id', '!=', $userId)->exists();
    }
    public function checkByFaceBookId($userId, $facebookId)
    {
        return $this->model->query()->where('facebook_id', $facebookId)->where('id', '!=', $userId)->exists();
    }

    public function checkByPhone($userId, $phone)
    {
        return $this->model->query()->where('phone', $phone)->where('id', '!=', $userId)->exists();
    }
    public function updateTypeUser($user)
    {
        $user->type_user = 1;
        $this->updateUser($user);
    }

    public function findUsersByAgencyId($agencyId, $perPage, $page)
    {
        return $this->model->where('agency_id', $agencyId)->withSum([
            'monthlyDiamondReceive as monthly_diamond_received' => function ($q) {
                $q->where('month', now()->month)
                    ->where('year', now()->year);
            }
        ], 'monthly_diamond_received')->orderBy('monthly_diamond_received', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }
    public function findUsersByAgencyIdI($agencyId)
    {
        return $this->model->where('agency_id', $agencyId)->withSum([
            'monthlyDiamondReceive as monthly_diamond_received' => function ($q) {
                $q->where('month', now()->month)
                    ->where('year', now()->year);
            }
        ], 'monthly_diamond_received')->orderBy('monthly_diamond_received', 'desc');
    }

    public function getIdsByAgencyId($agencyId)
    {
        return $this->model->query()->where('agency_id', $agencyId)->pluck("id")->toArray();
    }

    public function getAgencyMangerByFilter($keyword)
    {
        return $this->model->query()
            ->with(['profile', 'ownAgency.mempers.profile'])
            ->select('*')->selectRaw("((LENGTH(users.uuid) - LENGTH(REPLACE(users.uuid, ?, ''))) / CHAR_LENGTH(users.uuid)) * 100 AS matching_percentage", [$keyword])->has('ownAgency')->where(function ($q) use ($keyword) {
            $q->where('uuid', 'like', '%' . $keyword . '%')
                ->orWhereHas('ownAgency', function ($query) use ($keyword) {
                    $query->where('id', 'like', '%' . $keyword . '%');
                });
        })->orderBy('matching_percentage', 'desc')->take(10)->get();
    }

    public function getUsersByJoinAgency($agencyId)
    {
        return $this->model->where("agency_id", $agencyId)->where("type_user", '!=', 0)->whereMonth("join_agency_date", date("m"))->get();
    }

    public function getByHost($agencyId, $hostId = null)
    {
        $hosts = $this->model->where("agency_id", $agencyId)->where("type_user", '!=', 0);
        if ($hostId != null) {
            $hosts = $hosts->where("id", $hostId);
        }
        return $hosts->get();
    }

    public function updateShowGift($user)
    {
        $user->stopshow_gift = !$user->stopshow_gift;
        $this->updateUser($user);
    }

    public function logout($user)
    {
        $user->is_logout = 1;
        $this->updateUser($user);
        $user->currentAccessToken()->delete();
        return true;
    }

    public function updateDress($user, $dressType, $packType, $wareId)
    {
        $user->update(['dress_' . $dressType[$packType] => $wareId]);
        return true;
    }

    public function nullDress($user, $type)
    {
        $user->update(['dress_' . $type => null]);
        return true;
    }

    public function getUsersById($ids, $coins)
    {
        return $this->model->whereIn("id", $ids)->where("di", "<", $coins)->pluck("name")->toArray();
    }

    public function findUser($id)
    {
        return $this->model->whereId($id)->first();
    }

    public function pluckUsersByIds($ids, $type)
    {
        return $this->model->whereIn("id", $ids)->pluck($type)->toArray();
    }

    public function getChatIds($user)
    {
        return $user->chats->pluck('id')->toArray();
    }

    public function updateCurrentChat($user, $chatRoomId)
    {
        $user->current_room_chat = $chatRoomId;
        $this->updateUser($user);
    }

    public function getByName($name)
    {
        return $this->model->where('name', 'LIKE', '%' . $name . '%')->select('id', 'name', 'img')->get();
    }

    public function userChatRoom($userId, $data, $exceptId)
    {
        $this->model->query()
            ->select('id')
            ->whereHas('followers', fn($q) => $q->where('user_id', $userId))
            ->whereHas('followeds', fn($q) => $q->where('followed_user_id', $userId))
            ->whereNotIn('id', $exceptId)
            ->chunk(400, function ($userIds) use ($userId, $data) {
                $userIds = $userIds->pluck('id')->toArray();
                $this->sendMessageToUsers($userId, $userIds, $data);
            });
    }

    public function sendMessageToUsers(int|string|null $userId, mixed $userIds, array $message): void
    {
        $timeZone = request()->hasHeader('tz') ? request()->header()['tz'][0] : 'UTC';
        dispatchJobToQueue(new SendMessageToAllUsers($userId, $userIds, $message, timezone: $timeZone), 'heavyProcessing');
    }

    public function updateManger($userId)
    {
        $this->model->where('id', $userId)->update([
            'is_manger' => true,
        ]);
        return true;
    }

    public function countByAgencyId($agencyId)
    {
        return $this->model->where('agency_id', $agencyId)->count();
    }


    public function updateAgencyId($user, $agencyId)
    {
        $user->update(['agency_id' => $agencyId]);
        return true;
    }

    public function changeAgencyForHost($oldAgencyId, $newAgencyId)
    {
        $users = $this->model->where('agency_id', $oldAgencyId)->where('type_user', 1)->get();

        foreach ($users as $user) {
            \App\Facades\UserHandling::changeUserAgency($user, $newAgencyId);
        }

        return true;
    }

    public function report($uuid, $agencyId, $month, $year, $perPage, $page)
    {
        return $this->model->where('agency_id', '!=', 0)->where('agency_id', '!=', '')->where('agency_id', '!=', null)->when(isset($uuid), function ($query) use ($uuid) {
            $query->where('uuid', $uuid);
        })->when(isset($agencyId), function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->whereHas('userSallary', function ($q) use ($month, $year) {
            $q->when(isset($month) && isset($year), function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            });
        })->with([
                    'userSallary' => function ($query) use ($month, $year) {
                        $query->when(isset($month) && isset($year), function ($query) use ($month, $year) {
                            $query->where('month', $month)->where('year', $year);
                        });
                    }
                ])->with('agency')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($user) use ($month, $year) {
                $user->total_diamonds = $user->getTotalDiamond($month, $year);
                $user->total_salary = $user->getTotalSallary($month, $year);
                $user->total_cut_amount = $user->getTotalCutAmount($month, $year);
                $user->final_salary = $user->getSalary($month, $year); // Based on the computed salary attribute
                return $user;
            });
    }

    public function allUsersPlay()
    {
        return $this->model->whereNotNull('game_id')->with(['profile:id,user_id,avatar'])->where('online', 1)->with('nowGame')->paginate(10);
    }

    public function friends($tasks): LengthAwarePaginator
    {
        $user = $this->model->where('id', auth()->id())->firstOrFail();

        return $user->friends()
            ->where('online', 1)
            ->whereHas('ownerRoom', function ($query) use ($tasks) {
                $query->where('type', 'live')
                    ->where('is_live', 1)
                    ->when(!empty($tasks), function ($q) use ($tasks) {
                        $q->whereNotIn('id', $tasks);
                    });
            })
            ->with([
                'profile:id,user_id,avatar',
                'ware',
                'UserVip',
                'packs',
                'ownerRoom' => function ($query) {
                    $query->where('type', 'live')->where('is_live', 1);
                }
            ])
            ->paginate(request('per_page'));
    }

    public function online()
    {
        $authUserId = auth()->id();

        $onlineUsers = $this->model
            ->where('online', 1)
            ->inRandomOrder()
            ->with([
                'chatRoomsAsUser' => function ($q) use ($authUserId) {
                    $q->where('user_id2', $authUserId)
                        ->withCount([
                            'messages as unread_messages_count' => function ($query) use ($authUserId) {
                                $query->where('user_id', '<>', $authUserId)
                                    ->where('status', '<>', 'seen');
                            }
                        ]);
                },
                'chatRoomsAsUser2' => function ($q) use ($authUserId) {
                    $q->where('user_id', $authUserId)
                        ->withCount([
                            'messages as unread_messages_count' => function ($query) use ($authUserId) {
                                $query->where('user_id', '<>', $authUserId)
                                    ->where('status', '<>', 'seen');
                            }
                        ]);
                },
            ])
            ->paginate(request('per_page', 10));

        return $onlineUsers;
    }


    public function agencyUsers($agencyId, $month, $year, $perPage, $page)
    {
        return $this->model->where('agency_id', $agencyId)->with([
            'targets' => function ($query) use ($agencyId, $month, $year) {
                $query->where('agency_id', $agencyId)->whereMonth('created_at', $month)->whereYear('created_at', $year);
            }
        ])->paginate($perPage, ['*'], 'page', $page);
    }


    public function exists(int $id): bool
    {
        return \Cache::remember(
            "user_exists_{$id}",
            now()->addMinutes(10),
            fn() => $this->model->where('id', $id)->exists()
        );
    }
    public function emailExists(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

}
