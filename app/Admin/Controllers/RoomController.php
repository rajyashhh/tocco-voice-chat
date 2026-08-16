<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Models\KickRecord;
use App\Models\Pk;
use App\Models\Room;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\EnteredRoom;
use App\Models\RoomCategory;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\ViewErrorBag;
use Encore\Admin\Facades\Admin;
use App\Models\Admin as AdminModel;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\HasResourceActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Log;
use Modules\LuckyBox\Entities\BoxUse;

class RoomController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'rooms';

    static $usersCache;
    protected static $microphoneCache = [];


    public function index(Content $content)
    {
        $content = $content->title(trans('Rooms'));

        if (Admin::user()->can('actions-switch' . $this->permission_name) || Admin::user()->can('*')) {
            $content = $content->row(function (Row $row) {
                $row->column(12, $this->grid2());
            });
        }

        $grid = $this->grid();
        $content = $content->body($grid);

        return parent::index($content);
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */

    public function show($id, Content $content)
    {
        if (!request()->has('tab')) {
            return redirect(url()->current() . '?tab=admins');
        }

        $room = Room::with(['owner.profile', 'roomLevel', 'roomCategory', 'microphones.user.profile'])
            ->withCount('roomVisitors')
            ->findOrFail($id);

        // 1. Admins
        $adminIds = collect(explode(',', $room->room_admin))->filter();
        $admins = User::whereIn('id', $adminIds)
            ->with('profile')
            ->get();

        // 2. Gifts
        $giftQuery = $room->gifts()
            ->with(['gift', 'sender.profile', 'receiver.profile']);

        if (request('sender_id')) {
            $giftQuery->where('sender_id', request('sender_id'));
        }
        if (request('receiver_id')) {
            $giftQuery->where('receiver_id', request('receiver_id'));
        }
        if (request('start_at')) {
            $giftQuery->whereDate('created_at', '>=', request('start_at'));
        }
        if (request('end_at')) {
            $giftQuery->whereDate('created_at', '<=', request('end_at'));
        }
        $gifts = $giftQuery->orderByDesc('created_at')->paginate(15);
        $totalDiamonds = $giftQuery->sum('giftPrice');

        // 3. Visitors, Microphone, Blacklist, Pagination
        // Mic positions
        $micPositions = [];
        $microphones = $room->microphones
            ->filter(fn($mic) => !is_null($mic->user_id) && $mic->user_id > 0)
            ->sortBy('position')
            ->values();
        foreach ($microphones as $mic) {
            $micPositions[$mic->user_id] = $mic->position + 1;
        }


        // Blacklist
        $blackList = [];
        if ($room->room_black) {
            $blackListItems = explode(',', $room->room_black);
            foreach ($blackListItems as $item) {
                $parts = explode('#', $item);
                if (count($parts) === 3) {
                    $userId   = $parts[0];
                    $kickTime = $parts[1];
                    $duration = $parts[2];
                    $endTime = $kickTime + $duration;
                    if (time() < $endTime) {
                        $blackList[$userId] = [
                            'kick_time' => $kickTime,
                            'duration'  => $duration / 60, // minutes
                            'remaining' => ceil(($endTime - time()) / 60) // min remaining
                        ];
                    }
                }
            }
        }

        // Main visitors collection: paginate at the DB level and order by mic
        // position (nulls last) via a correlated subquery so the full set is
        // never loaded into memory.
        $micPositionSub = \DB::table('room_microphones')
            ->select('position')
            ->whereColumn('room_microphones.user_id', 'room_visitors.user_id')
            ->where('room_microphones.room_id', $room->id)
            ->whereNotNull('room_microphones.user_id')
            ->where('room_microphones.user_id', '>', 0)
            ->limit(1);

        $visitors = $room->roomVisitors()
            ->with('user.profile')
            ->select('room_visitors.*')
            ->selectSub($micPositionSub, 'mic_sort_position')
            ->orderByRaw('mic_sort_position IS NULL ASC')
            ->orderBy('mic_sort_position')
            ->paginate(15);

        $visitors->getCollection()->transform(function ($visitor) use ($micPositions, $blackList) {
            $visitor->mic_position = $micPositions[$visitor->user_id] ?? null;
            $visitor->kick_info = $blackList[$visitor->user_id] ?? null;
            return $visitor;
        });

        // 4. PKs (Room PKs)
        $pks = Pk::where('room_id', $room->id)
            ->with(['team1Boss.profile', 'team2Boss.profile'])
            ->orderByDesc('created_at')
            ->paginate(15);

        // 5. Boxes in Room
        $query = BoxUse::where('room_id', $room->id)
            ->with(['user.profile', 'picks', 'box']);

        if (request('type') !== null && request('type') !== '') {
            $query->where('type', request('type'));
        }

        if (request('status')) {
            $now = \Carbon\Carbon::now()->timestamp;
            switch (request('status')) {
                case 'active':
                    $query->where('is_closed', false)->where('end_at', '>', $now);
                    break;
                case 'closed':
                    $query->where('is_closed', true);
                    break;
                case 'expired':
                    $query->where('end_at', '<=', $now);
                    break;
            }
        }

        $boxes = $query->orderByDesc('created_at')->paginate(15);

        $roomTypes = RoomCategory::where('enable', 1)->get();

        $roomModes = [
            '0' => 10,
            '1' => 16,
            '2' => 12,
            '3' => 9,
            '4' => 4,
            '5' => 3,
            '6' => 21,
            '8' => 8,
        ];

        return parent::show($id, $content
            ->title(__('Room Profile'))
            ->description(__('Room Details'))
            ->body(view('room_profile', [
                'room'          => $room,
                'admins'        => $admins,
                'gifts'         => $gifts,
                'totalDiamonds' => $totalDiamonds,
                'visitors'      => $visitors,
                'pks'           => $pks,
                'boxes'         => $boxes,
                'roomTypes'     => $roomTypes,
                'roomModes'     => $roomModes,
                'errors' => new ViewErrorBag(),
            ])));
    }
    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Rooms'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Rooms'))
            ->body($this->form()));
    }

    protected function grid2()
    {
        $make_rooms_top = Cache::rememberForever('rooms_make_rooms_top', function () {
            return settings()->get('make_rooms_top');
        });
        return (new Box(
            title: __('admin.Actions'),
            content: view('admin.grid.users.RoomsChange', compact(['make_rooms_top'])),
        ));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    protected function grid()
    {
        $grid = new Grid(new Room);
        $grid->model()->with(['microphones.user.profile']);
        $filterType = request('filter', 'all');
        $user = auth()->user();

        $grid->header(fn() => $this->buildTabsHeader($filterType));

        $this->setupBaseModel($grid, $user);
        $this->applyFilterType($grid, $filterType, $user);
        $this->setupFilters($grid);
        $this->defineGridColumns($grid);
        $grid->disableExport();

        $this->extendGrid($grid);
        $this->setupPinModalScript();

        return $grid;
    }

    protected function buildTabsHeader(string $filterType): string
    {
        return Cache::remember("tabs_header_$filterType", now()->addMinutes(10), function () use ($filterType) {
            $tabs = [
                // 'all'         => __('All'),
                // 'popular'     => __('Popular'),
                // 'last_create' => __('New'),
                // 'pk'          => __('PK'),
                // 'close_room'  => __('close room'),
                // 'hide_room'   => __('hide room'),
                // 'country'     => __('countries'),

                'all'         => __('dashboard.all'),
                'popular'     => __('dashboard.popular'),
                'last_create' => __('dashboard.new'),
                'pk'          => __('dashboard.pk'),
                'close_room'  => __('dashboard.close_room'),
                'hide_room'   => __('dashboard.hide_room'),
                'country'     => __('dashboard.country'),
            ];

            $html = '<div class="nav-tabs-custom"><ul class="nav nav-tabs">';
            foreach ($tabs as $key => $label) {
                $active = $filterType === $key ? 'active' : '';
                $url = request()->fullUrlWithQuery(['filter' => $key]);
                $html .= "<li class='{$active}'><a href='{$url}' class='tab-link'>{$label}</a></li>";
            }
            $html .= '</ul></div>';

            $html .= <<<HTML
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const tabLinks = document.querySelectorAll('.tab-link');
                        const loader = document.getElementById('tab-loading');
                        tabLinks.forEach(function (tab) {
                            tab.addEventListener('click', function (e) {
                                e.preventDefault();
                                loader.style.display = 'block';
                                tabLinks.forEach(t => t.style.pointerEvents = 'none');
                                setTimeout(() => {
                                    window.location.href = tab.getAttribute('href');
                                }, 300);
                            });
                        });
                    });
                </script>
            HTML;

            return $html;
        });
    }


    protected function setupBaseModel(Grid $grid, $user): void
    {
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->audio()
            ->select(
                'rooms.id',
                'rooms.uid',
                'rooms.microphone',
                'rooms.pin',
                'rooms.max_admin',
                'rooms.is_top',
                'rooms.top_room',
                'rooms.room_name',
                'rooms.room_cover',
                'rooms.room_admin',
                'rooms.level',
                'rooms.level_id',
                \DB::raw("
        CASE rooms.room_status
            WHEN 1 THEN 100
            WHEN 2 THEN 10
            ELSE 80
        END AS status_priority,
        (SELECT GROUP_CONCAT(user_id)
         FROM room_visitors
         WHERE room_visitors.room_id = rooms.id) AS visitor_ids
     ")
            )

            ->with([
                'roomLevel',
                'owner' => fn($q)  => $q->with([
                    'packs' => fn($q2) => $q2->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                    'profile:id,user_id,avatar',
                    'country:id,flag,name,e_name',
                    'senderLevel',
                    'receiverLevel',

                ])->select(['id', 'uuid', 'special_id', 'name', 'country_id', 'sender_level', 'received_level',]),
                'microphones' => function ($q) {
                    $q->orderBy('position');
                },
                'microphones.user:id,name',
                'microphones.user.profile:id,user_id,avatar',

            ])
            ->when($countryID, fn($q) => $q->whereHas('owner.country', function ($q) use ($countryID) {
                $q->whereIn('id',  $countryID);
            }))
            ->withCount('roomVisitors');

        // ✅ كاش make_rooms_top
        $makeRoomsTop = Cache::rememberForever('rooms_make_rooms_top', function () {
            return settings()->get('make_rooms_top') ?? 0;
        });

        // ✅ ترتيب الغرف (same as API endpoint RoomRepository::all)
        // rooms with room_status = 0 always at end
        $orderSql = [];
        $orderSql[] = 'CASE WHEN rooms.room_status = 0 THEN 1 ELSE 0 END ASC';
        $orderSql[] = 'pin DESC';
        if ($makeRoomsTop == 1) {
            $orderSql[] = 'is_top = 1 DESC';
        }
        $orderSql[] = 'room_visitors_count DESC';
        $orderSql[] = 'hour_hot DESC';

        if (request()->online == 1) {
            $grid->model()->whereHas('roomVisitors');
        }

        $grid->model()->orderByRaw(implode(', ', $orderSql));
    }


    protected function applyFilterType(Grid $grid, string $filterType, $user): void
    {
        // ✅ Same filter ordering as API RoomRepository::all()
        // Base ordering (pin, is_top, room_visitors_count, hour_hot) is already set in setupBaseModel
        switch ($filterType) {
            case 'boss':
                $cacheKey = "user:{$user->id}:rooms:boss";
                $roomIds = Cache::remember($cacheKey, 60, function () use ($user) {
                    return EnteredRoom::query()
                        ->where('uid', $user->id)
                        ->orderByDesc('entered_at')
                        ->pluck('rid')
                        ->toArray();
                });
                $grid->model()->whereIn('id', $roomIds);
                break;

            case 'trend':
                $grid->model()
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'popular':
                $grid->model()
                    ->orderByDesc('top_room');
                break;

            case 'last_create':
                $grid->model()
                    ->whereDate('created_at', '>=', now()->subDays(3))
                    ->orderByDesc('id');
                break;

            case 'pk':
                $grid->model()
                    ->has('lastPk');
                break;

            case 'party':
                $grid->model()
                    ->whereHas('roomCategory', fn($q) => $q->where('type', 'party'));
                break;

            case 'recently':
            case 'festival':
                $grid->model()
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'interested':
                $cacheKey = "user:{$user->id}:rooms:interested";
                $roomTypes = Cache::remember($cacheKey, 60, function () use ($user) {
                    return EnteredRoom::query()
                        ->where('uid', $user->id)
                        ->where('entered_at', '>=', now()->subDay())
                        ->with('room:id,room_type')
                        ->get()
                        ->pluck('room.room_type')
                        ->unique()
                        ->toArray();
                });

                if (!empty($roomTypes)) {
                    $grid->model()
                        ->whereIn('room_type', $roomTypes)
                        ->orderByDesc('top_room')
                        ->orderByDesc('session');
                }
                break;

            case 'nearby':
                $coords = [$user->lat, $user->long, $user->lat];
                $grid->model()
                    ->selectRaw(
                        'rooms.*, (6371 * acos(cos(radians(?)) * cos(radians(owner.lat)) * cos(radians(owner.long) - radians(?)) + sin(radians(?)) * sin(radians(owner.lat)))) AS distance',
                        $coords
                    )
                    ->join('users as owner', 'rooms.uid', '=', 'owner.id')
                    ->orderBy('distance');
                break;

            case 'top_gift':
                $grid->model()
                    ->withSum('gifts as total_gift_exp', 'giftPrice')
                    ->orderByDesc('total_gift_exp');
                break;

            case 'close_room':
                $grid->model()->whereHas('bans');
                break;

            case 'hide_room':
                $grid->model()->whereHas('owner')
                    ->whereHas('owner.packs', function ($q) {
                        $q->where('type', 16)
                            ->where('is_used', 1)
                            ->where(function ($q) {
                                $q->where('expire', 0)
                                    ->orWhere('expire', '>=', now()->timestamp);
                            });
                    });
                break;

            case 'country':
                $grid->model()
                    ->join('users', 'rooms.uid', '=', 'users.id')
                    ->join('countries', 'users.country_id', '=', 'countries.id')
                    ->orderBy('countries.id');
                break;

            default:
                // No extra ordering - base ordering handles it
                break;
        }
    }

    protected function setupFilters(Grid $grid): void
    {
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas('owner', fn($q) => $q->where('name', 'like', "%$input%")
                        ->orWhere('special_id', 'like', "%$input%")
                        ->orWhere('uuid', 'like', "%$input%"));
                }, __('User'))->placeholder(__('Search by name or numId'));

                // $countries = Cache::rememberForever('filter_countries_list', function () {
                //     return \App\Models\Country::query()->pluck('name', 'id');
                // });

                $locale = app()->getLocale(); // 'ar', 'en', etc.
                $column = $locale === 'ar' ? 'name' : 'e_name';

                $countries = \App\Models\Country::query()->pluck($column, 'id');


                $filter->where(function ($query) {
                    if ($this->input) {
                        $query->whereHas('owner', fn($q) => $q->where('country_id', $this->input));
                    }
                }, __('Country'))->select($countries);
            });
        });
    }





    protected function defineGridColumns($grid)
    {
        $maxRoomAdmin = Common::getConfig('max_room_admin') ?? 4;

        // Preload users for this page only
        $grid->model()->with('microphones')->collection(function ($collection) {
            // collect all microphone user IDs from the current page rows
            $allIds = $collection->flatMap(function ($row) {
                return array_filter(explode(',', (string) $row->microphone));
            })->unique()->values()->all();

            // fetch all needed users once
            $users = collect();
            if (!empty($allIds)) {
                $users = User::select(['id', 'name'])
                    //->with('profile:id,user_id,avatar')
                    ->whereIn('id', $allIds)
                    ->get()
                    ->keyBy('id');
            }

            // attach a ready-to-use collection on each row
            $collection->each(function ($row) use ($users) {
                $ids = array_filter(explode(',', (string) $row->microphone));
                $row->microphone_users = collect($ids)
                    ->map(fn($id) => $users->get($id))
                    ->filter()
                    ->values();
            });

            return $collection; // IMPORTANT: return the collection
        });

        $grid->column('pin', __('Pin Status'))->display(function ($pin) {
            return $pin == 1
                ? '<span class="text-success"> <i class="fa fa-thumb-tack"></i></span>'
                : '<span class="text-muted"> </span>';
        });

        $grid->id(__('ID'));


        $grid->column('room_name', __('room'))->display(function ($name) {

            $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');

            if (mb_strlen($name) > 50) {
                $name = mb_substr($name, 0, 50) . ' ...';
            }

            $name = e($name);

            $path = $this->room_cover;
            $id = $this->id;

            $defaultImage = asset("images/room.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            if (strlen($name) > 50) {
                $name = substr($name, 0, 50) . ' ...';
            }

            $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
            $encodedName = htmlspecialchars($cleanName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $levelimage = @$this->roomLevel?->img ? getImagePath(@$this->roomLevel->img ?? '') : null;
            $levelImageHtml = '';

            if ($levelimage) {
                $levelImageHtml = "
                    <div style='margin-top:4px;'>
                        <img src='{$levelimage}' style='width:32px;height:30px;margin-right:2px;'>
                    </div>
                ";
            }

            $roomUrl = url("admin/rooms/{$id}");
            return "
                <a href='$roomUrl' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        <img src='$url' alt='Room Image' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;'>
                        <div>
                            <span style='cursor: pointer;'>$encodedName</span><br>
                            <span style='cursor: pointer;'>ID: $id</span>
                             {$levelImageHtml}
                        </div>
                    </div>
               ";
        });


        $grid->column('owner_id', __('room owner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('max_admin', __('Max Admin'))->display(function ($maxAdmin) use ($maxRoomAdmin) {
            $adminsCount = is_array($this->admins) ? count($this->admins) : 0;
            return $adminsCount . '/' . ($maxAdmin ?? $maxRoomAdmin);
        });


        $grid->column('id', __('Number of users'))->display(fn() => $this->room_visitors_count ?? 0);


        $grid->column(__('microphone'))->display(function () {

            $microphones = $this->microphones->sortBy('position');


            if ($microphones->isEmpty()) {
                return '';
            }

            $html = '<div class="image-container">';

            foreach ($microphones as $mic) {
                $user = $mic->user;

                if (!$user) continue;

                $url = $user->profile?->avatar
                    ? getImagePath($user->profile->avatar)
                    : asset("images/businessman-icon.jpg");

                $name = e($user->name);
                $id   = e($user->id);
                $userUrl = admin_url('users/' . $user->id);
                $html .= <<<HTML
                <div class="image-wrapper" onclick="window.location.href='{$userUrl}'">
                    <img src="{$url}" title="{$name}"
                    style="width: 40px; height: 40px; border-radius: 50%;
                            object-fit: cover; border: 2px solid white;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                            transition: transform 0.3s ease;"/>
                </div>
             HTML;
            }

            $html .= '</div>';

            // Add the same CSS block only once
            static $appended = false;
            if (!$appended) {
                $html .= '
              <style>



                .image-container {
                    display: flex;
                    justify-content: start;
                    align-items: center;
                    gap: -10px; /* Overlap the images slightly */
                    padding: 8px 0;
                    overflow-y: overlay;
                    width: 218px;
                    padding-right: 16px;
                }
                .image-wrapper {
                    display: inline-block;
                    position: relative;
                    margin-right: -12px;
                }
                .image-wrapper img {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 2px solid #fff;
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    cursor: pointer;
                }
                .image-wrapper img:hover {
                    transform: scale(1.2);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                }
                </style>';
                $appended = true;
            }

            return $html;
        });





        Admin::style('
        .dropdown-backdrop {
            position: absolute !important;

        }
        html.ltr .dropdown-menu {

            right: 38px !important;
        }

    ');
    }








    public function getRoomMicrophones($roomId)
    {
        $room = Room::find($roomId);
        if (!$room) return response()->json([]);

        $users = collect(explode(',', $room->microphone))
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->values()
            ->all();

        if (empty($users)) return response()->json([]);

        $profiles = User::with('profile:id,user_id,avatar')
            ->whereIn('id', $users)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile?->avatar ? getImagePath($user->profile->avatar) : null,
                ];
            })
            ->values();

        return response()->json($profiles);
    }

    // New batched endpoint to fetch microphones for multiple rooms at once
    public function getRoomsMicrophones(Request $request)
    {
        $roomsParam = $request->get('rooms');
        if (!$roomsParam) return response()->json([]);

        $roomIds = is_array($roomsParam) ? $roomsParam : explode(',', $roomsParam);
        $roomIds = array_filter(array_map('intval', $roomIds));
        if (empty($roomIds)) return response()->json([]);

        // fetch microphone rows for all requested rooms in one query
        $micRows = \DB::table('room_microphones')
            ->whereIn('room_id', $roomIds)
            ->orderBy('position')
            ->get(['room_id', 'user_id', 'position']);

        $userIds = collect($micRows)->pluck('user_id')->filter()->unique()->values()->all();

        $users = [];
        if (!empty($userIds)) {
            $users = User::with('profile:id,user_id,avatar')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');
        }

        $result = [];
        foreach ($micRows as $row) {
            if (!$row->user_id) continue;
            $u = $users->get($row->user_id);
            if (!$u) continue;
            $result[$row->room_id][] = [
                'id' => $u->id,
                'name' => $u->name,
                'avatar' => $u->profile?->avatar ? getImagePath($u->profile->avatar) : null,
                'position' => $row->position,
            ];
        }

        return response()->json($result);
    }










    public function updatePinStatus($id, Request $request)
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        try {
            $room = Room::findOrFail($id);
            $room->pin = request('pin');
            $room->save();

            return response()->json([
                'success' => true,
                'message' => request('pin')
                    ? 'Room pinned successfully'
                    : 'Room unpinned successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating pin status: ' . $e->getMessage()
            ]);
        }
    }
    protected function setupPinModalScript()
    {
        $token = csrf_token();

        $confirm = __('Confirm Pin Room');
        $doyouwant = __('Do you want to pin this room to the top?');
        $confirm = __('Confirm');
        $cancel  = __('admin.cancel');
        Admin::html(<<<HTML
            <div class="modal fade" id="pinRoomModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{$confirm}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>{$doyouwant}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{$cancel}</button>
                            <button type="button" class="btn btn-primary confirm-pin">{$confirm}</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            $(document).ready(function() {
                var currentRoomId = null;
                var currentBtn = null;

                $('.pin-room-btn').click(function() {
                    currentRoomId = $(this).data('room');
                    currentBtn = $(this);
                    var isPinned = $(this).data('pinned') === 'true';

                    if (isPinned) {
                        // If already pinned, unpin immediately without confirmation
                        updatePinStatus(currentRoomId, false);
                    } else {
                        // Show confirmation modal for pinning
                        $('#pinRoomModal').modal('show');
                    }
                });

                $('.confirm-pin').click(function() {
                    $('#pinRoomModal').modal('hide');
                    updatePinStatus(currentRoomId, true);
                });

                function updatePinStatus(roomId, pin) {
                    $.ajax({
                        url: '/admin/rooms/' + roomId + '/update-pin-status',
                        type: 'POST',
                        data: {
                            pin: pin ? 1 : 0,
                            _token: '{$token}',
                            _method: 'PUT'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update button appearance without reloading
                                currentBtn.data('pinned', pin ? 'true' : 'false');
                                currentBtn.find('i')
                                    .toggleClass('fa-thumb-tack', !pin)
                                    .toggleClass('fa-check-circle', pin)
                                    .parent()
                                    .toggleClass('text-muted', !pin)
                                    .toggleClass('text-success', pin);

                                // Show success message
                                toastr.success(response.message);

                                // If you want to refresh the grid instead of updating just the button:
                                // $.admin.reload();
                            } else {
                                toastr.error(response.message || 'Operation failed');
                            }
                        },
                        error: function() {
                            toastr.error('Request failed');
                        }
                    });
                }
            });
            </script>
            HTML);
    }
    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Room::findOrFail($id));
        //$show->id('ID');
        //$show->numid('numid');
        //$show->uid('uid');
        //$show->room_status('room_status');
        //$show->room_name('room_name');
        //$show->room_cover('room_cover');
        //$show->room_intro('room_intro');
        //$show->room_pass('room_pass');
        //$show->room_class('room_class');
        //$show->room_type('room_type');
        //$show->room_welcome('room_welcome');
        //$show->room_admin('room_admin');
        //$show->room_visitor('room_visitor');
        //$show->room_speak('room_speak');
        //$show->room_sound('room_sound');
        //$show->room_black('room_black');
        //$show->ranking('ranking');
        //$show->is_popular('is_popular');
        //$show->secret_chat('secret_chat');
        //$show->is_top('is_top');
        //$show->sort('sort');
        //$show->room_background('room_background');
        //$show->microphone('microphone');
        //$show->is_afk('is_afk');
        //$show->hot('hot');
        //$show->room_judge('room_judge');
        //$show->is_prohibit_sound('is_prohibit_sound');
        //$show->is_recommended('is_recommended');
        //$show->play_num('play_num');
        //$show->free_mic('free_mic');
        //$show->created_at(__('admin.created_at'));
        //$show->updated_at(__('admin.updated_at'));
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Room);
        $this->disableFormTools($form);
        if (!$form->isEditing()) {
            $form->hidden('numid', __('numid'))->default(rand(111111, 999999));
        } else {
            $form->text('numid', __('numid'));
        }
        $form->hidden('type', __('type'))->default('audio');
        $form->select('uid', __('owner room'))->options($this->ownerOptions())->ajax('/api/search/users7', 'id', 'name')->rules('required');
        $form->switch('room_status', __('room status'))->options(Common::getSwitchStates());
        $form->switch('top_room', __('top room'))->options(Common::getSwitchStates());
        $form->switch('pin', __('pin'))->options(Common::getSwitchStates());
        $form->text('room_name', __('room name'))->rules('required');
        $form->image('room_cover', __('room cover'));
        $form->text('room_intro', __('room intro'));
        $form->text('room_pass', __('room pass'))->rules('nullable|integer|digits:6');
        $form->hidden('is_afk', __('owner in'));
        $form->select('room_class')->options(function () {
            $options = [];
            $cats = RoomCategory::query()->where('enable', 1)->where('parent_id', 0)->get();
            foreach ($cats as $cat) {
                $options[$cat->id] = $cat->name;
            }
            return $options;
        })->load('room_type', '/admin/api/room-subcategories');
        $form->select('room_type', __('room type'))->options(function () {
            $options = [];
            $parentId = $this->room_class ?? 0;
            if ($parentId) {
                $cats = RoomCategory::query()->where('enable', 1)->where('parent_id', $parentId)->get();
                foreach ($cats as $cat) {
                    $options[$cat->id] = $cat->name;
                }
            }
            return $options;
        });
        $form->text('room_welcome', __('room welcome'));
        $form->number('sort_num', __('Sort Num'))->default(0);

        // Add loading indicator when room_class changes and room_type is loading
        Admin::script(<<<JS
            $(document).ready(function() {
                var roomTypeSelect = $('select[name="room_type"]').closest('.form-group');
                
                $('select[name="room_class"]').on('change', function() {
                    // Disable room_type and show loading
                    var select = $('select[name="room_type"]');
                    select.prop('disabled', true);
                    roomTypeSelect.css('opacity', '0.5');
                    
                    // Add a loading text
                    select.empty().append('<option value="">Loading...</option>');
                    select.trigger('change.select2');
                });

                // Re-enable after AJAX completes
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && settings.url.indexOf('room-subcategories') !== -1) {
                        var select = $('select[name="room_type"]');
                        select.prop('disabled', false);
                        roomTypeSelect.css('opacity', '1');
                    }
                });
            });
        JS);

        return $form;
    }

    protected function ownerOptions($editing = false)
    {
        return function ($value) use ($editing) {
            $ops = [];
            foreach (User::where('id', $value)->get() as $user) {
                $ops[$user->id] = $user->uuid ?? $user->id . '_' . $user->name;
            }
            return $ops;
        };
    }

    public function removeAdmin(Request $request, $roomId)
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        $room = Room::findOrFail($roomId);
        $adminId = $request->admin_id;

        $admin = AdminModel::find($adminId);

        // Dual-delete through the repository: removes the room_administrators
        // row AND re-syncs the legacy rooms.room_admin string. Editing the
        // string alone left a stale table row, so the app's add_admin_to_room
        // kept answering 444 "already an administrator" for re-adds.
        app(\App\Repositories\RoomAdministratorRepository::class)
            ->removeAdmin($room->id, (int) $adminId);
        $room->refresh();

        $adminName = $admin?->name ?? __('Unknown');
        $comment = __(':name has been removed from administrators.', ['name' => $adminName]);

        $d = [
            "messageContent" => [
                "message" => "banAdmin",
                "roomId" => $room->id,
                "adminId" => $adminId,
                "comment" => $comment
            ]
        ];
        $json = json_encode($d);

        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, $json);

        return response()->json([
            'success' => true,
            'message' => __('Administrator removed successfully')
        ]);
    }

    public function addVisitor(Request $request, $roomId)
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        $room = Room::findOrFail($roomId);

        if ($room->roomVisitors()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('User is already a visitor in this room')
            ]);
        }

        $room->roomVisitors()->create([
            'user_id' => $request->user_id,
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Visitor added successfully')
        ]);
    }

    public function kickVisitor(Request $request, $roomId): JsonResponse
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        $room = Room::findOrFail($roomId);
        $visitorId = $request->user_id;
        $duration = $request->minutes ?? 5;

        if ($visitorId == $room->uid) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot kick the room owner')
            ]);
        }

        if (Common::pack_get(9, $visitorId)) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot kick this user')
            ]);
        }

        $blackList = $room->room_black;

        if (empty($blackList)) {
            $blackList = $visitorId . '#' . time() . '#' . ($duration * 60);
        } else {
            $list = explode(',', $blackList);
            $newList = [];

            foreach ($list as &$item) {
                $black = explode('#', $item);
                if (isset($black[0]) && $black[0] != $visitorId && $item !== "") {
                    $newList[] = $item;
                }
            }

            $newList = array_filter($newList);

            $blackList = implode(',', $newList) ?: null;
        }

        $room->room_black = $blackList;
        $room->save();

        Common::quit_hand($room->uid, $visitorId);

        $user = User::find($visitorId);
        if ($user) {
            $user->now_room_uid = 0;
            $user->save();
        }

        KickRecord::create([
            "kicked_user_id" => auth()->id(),
            "user_id" => $visitorId,
            "room_id" => $room->id,
            "type" => 'admin'
        ]);

        $message = __('api.blockRoom', [
            'name' => $user->name ?? 'Unknown',
            'actionName' => auth()->user()->name,
            'duration' => $duration
        ], 'ar');

        $d = [
            "messageContent" => [
                "message" => "kickVisitorOut",
                'duration' => $duration,
                "visitorId" => $visitorId,
                "comment" => $message
            ]
        ];
        $json = json_encode($d);

        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, $json);

        Common::calcTime($visitorId);

        return response()->json([
            'success' => true,
            'message' => __('Visitor kicked successfully')
        ]);
    }

    public function unbanVisitor(Request $request, $roomId): JsonResponse
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        $room = Room::findOrFail($roomId);
        $visitorId = $request->user_id;

        if (!$room->room_black) {
            return response()->json(['success' => false, 'message' => __('User is not banned')]);
        }

        $list = explode(',', $room->room_black);
        $newList = [];

        foreach ($list as $item) {
            $black = explode('#', $item);
            if ($black[0] != $visitorId) {
                $newList[] = $item;
            }
        }

        $room->room_black = implode(',', $newList);
        $room->save();

        return response()->json(['success' => true, 'message' => __('User has been unbanned')]);
    }

    public function getUsers(Request $request)
    {
        $userIds = $request->user_ids;

        $users = User::whereIn('id', array_filter($userIds))
            ->with('profile:user_id,avatar')
            ->get(['id', 'name', 'uuid']);

        $users = $users->map(function ($user) {
            $avatar = $user->profile->avatar ?? null;
            $user->avatar_url = getImagePath($avatar);
            return $user;
        });

        return response()->json($users);
    }

    public function updateBasicInfo(Request $request, $id)
    {
        if (!Admin::user()->can('actions-switch' . $this->permission_name) && !Admin::user()->can('*')) abort(403);
        $room = Room::findOrFail($id);

        $room->room_name = $request->room_name;
        $room->room_type = $request->room_type;
        $room->max_admin = $request->max_admin;
        $room->is_popular = $request->has('is_popular');
        $room->is_top = $request->has('is_top');
        $room->is_recommended = $request->has('is_recommended');
        $room->secret_chat = $request->has('secret_chat');

        $room->save();

        return response()->json([
            'success' => true,
            'message' => __('Room updated successfully!')
        ]);
    }
}
