<?php
namespace App\Repositories\Room;
use App\Helpers\CacheHelper;
use App\Helpers\Common;
use App\Models\EnteredRoom;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoomRepo implements RoomRepoInterface {

    public $model;
    public function __construct (Room $model)
    {
        $this->model = $model;
    }

    public function all ( $req )
    {
        $user = $req->user();
        $result = $this->model->with([
            'boxUse' => fn($q) => $q->where('not_used_num', '>=', 1),
            'backgroundImage'
        ])
        ->whereHas('owner')
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->where('count_room_socket', '!=', 0)
                  ->where('top_room', 1);
            })->orWhere(function ($q) {
                $q->where('count_room_socket', '!=', 0);
            });
        })
        ->where('room_status', 1);

        // Filter by country if provided
        if (!is_null($req->country_id)) {
            $result->whereHas('owner', function ($q) use ($req) {
                $q->where('country_id', $req->country_id);
            });
        }

        // Apply filters based on 'filter' parameter
        switch ($req->filter) {
            case 'boss':
                $roomIds = EnteredRoom::query()
                    ->where('uid', $user->id)
                    ->orderByDesc('entered_at')
                    ->pluck('rid')
                    ->toArray();
                $result->whereIn('id', $roomIds);
                break;

            case 'trend':
                $result->orderBy('top_room', 'DESC')
                    ->orderByDesc('session');
                break;

            case 'popular':
                $result->orderByDesc('top_room')
                    ->orderByDesc('count_room_socket');
                break;

            case 'festival':
                $result->orderByDesc('top_room')
                    ->orderByDesc('session')
                    ->orderByDesc('count_room_socket');
                break;

            case 'nearby':
                $userLat  = $user->lat;
                $userLong = $user->long;

                // Use lat/long from the related `owner` (User) model
                $result->selectRaw(
                        '*,
                        ( 6371 * acos( cos( radians(?) ) * cos( radians( owner.lat ) ) * cos( radians( owner.long ) - radians(?) ) + sin( radians(?) ) * sin( radians( owner.lat ) ) ) ) AS distance',
                        [$userLat, $userLong, $userLat]
                    )
                    ->join('users as owner', 'rooms.uid', '=', 'owner.id')
                    ->orderBy('distance');
                break;

            default:
                $result->orderByDesc('hour_hot');
                break;
        }
        // Paginate the results with 10 items per page
        return $result->paginate(10);
    }

    public function find ( $id )
    {
        return $this->model->where('uid', $id)->first();
    }

    public function findByType ( $id ,$type)
    {
        return $this->model->where('uid', $id)->where('type' , $type)->first();
    }

    public function create ( $data )
    {
        // dd($data);
        return $this->model->create($data);
    }

    public function update ( $req , $id )
    {
        // TODO: Implement update() method.
    }

    public function delete ( $id )
    {
        // TODO: Implement delete() method.
    }

    public function save ($model)
    {
        CacheHelper::forget('rooms');
        $model->save ();
        return $model;
    }

    public function getAllOpening()
    {
        return $this->model
            ->orderBy('top_room','DESC')
            ->where('room_status',1)
            ->get();

    }

    public function getAllOpeningIds()
    {
        return $this->model
            ->where('room_status',1)
            ->pluck('id')
            ->toArray();

    }
}
