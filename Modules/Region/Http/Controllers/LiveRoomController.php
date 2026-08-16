<?php

namespace Modules\Region\Http\Controllers;


use App\Models\Pk;
use App\Models\Room;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\KickRecord;
use App\Models\EnteredRoom;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use App\Admin\Services\UserService;
use App\Models\Admin as AdminModel;
use Illuminate\Support\Facades\Cache;
use Modules\LuckyBox\Entities\BoxUse;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class LiveRoomController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'rooms';

    static $usersCache;
    protected static $microphoneCache = [];


    public function index(Content $content)
    {
        $content = $content->title(trans('Rooms'));

        // if (Admin::user()->can('actions-switch' . $this->permission_name) || Admin::user()->can('*')) {
        //     $content = $content->row(function (Row $row) {
        //         $row->column(12, $this->grid2());
        //     });
        // }

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
        $room = Room::with(['owner.profile', 'roomCategory'])
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
        if ($room->microphone) {
            $positions = explode(',', $room->microphone);
            foreach ($positions as $index => $userId) {
                if ($userId != '0') {
                    $micPositions[$userId] = $index + 1;
                }
            }
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

        // Main visitors collection
        $visitorsRaw = $room->roomVisitors()
            ->with('user.profile')
            ->get()
            ->map(function ($visitor) use ($micPositions, $blackList) {
                $visitor->mic_position = $micPositions[$visitor->user_id] ?? null;
                $visitor->kick_info = $blackList[$visitor->user_id] ?? null;
                return $visitor;
            })
            ->sortBy(function ($visitor) {
                return $visitor->mic_position === null ? PHP_INT_MAX : $visitor->mic_position;
            });

        // 4. PKs (Room PKs)
        $pks = Pk::where('room_id', $room->id)
            ->with(['team1Boss.profile', 'team2Boss.profile'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $currentPage = request()->get('page', 1);
        $perPage = 15;
        $visitors = new \Illuminate\Pagination\LengthAwarePaginator(
            $visitorsRaw->forPage($currentPage, $perPage)->values(),
            $visitorsRaw->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

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
                'roomModes'     => $roomModes
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
        $grid->model()->where('type', 'live');
        $filterType = request('filter', 'all');
        $user = auth()->user();
        $this->setupBaseModel($grid, $user);
        $this->applyFilterType($grid, $filterType, $user);
        $this->setupFilters($grid);
        $this->defineGridColumns($grid);
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableExport();

        $this->extendGrid($grid);
        $this->setupPinModalScript();

        return $grid;
    }

    protected function buildTabsHeader(string $filterType): string
    {
        return Cache::remember("tabs_header_$filterType", now()->addMinutes(10), function () use ($filterType) {
            $tabs = [
                'all'         => __('All'),
                'popular'     => __('Popular'),
                'last_create' => __('New'),
                'pk'          => __('PK'),
                'close_room'  => __('close room'),
                'hide_room'   => __('hide room'),
                'country'     => __('countries'),
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

        $countries = Common::areaCountries();

        $grid->model()
            ->select("id", 'uid', 'microphone', 'pin', 'max_admin', 'pin', 'is_top', 'top_room', "room_name", "room_cover", "room_admin", \DB::raw("
                CASE room_status
                    WHEN 1 THEN 100
                    WHEN 2 THEN 10
                    ELSE 80
                END AS status_priority,
                (SELECT GROUP_CONCAT(user_id)
                 FROM room_visitors
                 WHERE room_visitors.room_id = rooms.id) AS visitor_ids
            "))
            ->with([

                'owner' => fn($q)  => $q->with([
                    'packs' => fn($q2) => $q2->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                    'profile:id,user_id,avatar'
                ])->select(['id', 'uuid', 'special_id', 'name']),

            ])->whereHas('owner.country', function ($q) use ($countries) {
                $q->whereIn('id',  $countries);
            })
            ->withCount('roomVisitors');

        $makeRoomsTop = Cache::rememberForever('rooms_make_rooms_top', function () {
            return settings()->get('make_rooms_top') ?? 0;
        });

        $orderSql = [];
        if ($makeRoomsTop == 1) {
            $orderSql[] = '(is_top = 1) DESC';
        }
        $orderSql[] = 'status_priority DESC';
        $orderSql[] = 'pin DESC';
        $orderSql[] = 'room_visitors_count DESC';

        if (request()->online == 1) {
            $grid->model()->whereHas('roomVisitors');
        }

        if (request()->is_live == 1) {
            $grid->model()->where('is_live', 1);
        }

        if (request()->is_live == 0 && !is_null(request()->is_live)) {
            $grid->model()->where('is_live', 0);
        }

        $grid->model()->orderByRaw(implode(', ', $orderSql));
    }


    protected function applyFilterType(Grid $grid, string $filterType, $user): void
    {
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
                    ->orderByDesc('pin')
                    // ->orderByDesc('room_visitors_count')
                    ->orderByDesc('session');
                break;

            case 'popular':
                $grid->model()
                    ->orderByDesc('top_room')
                    ->orderByDesc('pin');
                // ->orderByDesc('room_visitors_count');
                break;

            case 'last_create':
                $grid->model()
                    ->whereDate('created_at', '>=', now()->subDays(3))
                    ->orderByDesc('pin')
                    ->orderByDesc('id');
                break;

            case 'pk':
                $grid->model()
                    ->has('lastPk')
                    ->orderByDesc('pin');
                break;

            case 'party':
                $grid->model()
                    ->whereHas('roomCategory', fn($q) => $q->where('type', 'party'))
                    ->orderByDesc('pin');
                break;

            case 'festival':
            case 'recently':
                $grid->model()
                    ->orderByDesc('pin')
                    ->orderByDesc('top_room')
                    // ->orderByDesc('room_visitors_count')
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
                        ->orderByDesc('pin')
                        ->orderByDesc('top_room')
                        ->orderByDesc('session');
                }
                break;

            case 'nearby':
                $cacheKey = "user:{$user->id}:rooms:nearby";
                $coords = [$user->lat, $user->long, $user->lat];

                $grid->model()
                    ->selectRaw(
                        'rooms.*, (6371 * acos(cos(radians(?)) * cos(radians(owner.lat)) * cos(radians(owner.long) - radians(?)) + sin(radians(?)) * sin(radians(owner.lat)))) AS distance',
                        $coords
                    )
                    ->join('users as owner', 'rooms.uid', '=', 'owner.id')
                    ->orderByDesc('pin')
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
                $grid->model()
                    ->orderByDesc('pin')
                    ->orderByDesc('hour_hot');
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

                $countries = Cache::rememberForever('filter_countries_list', function () {
                    return \App\Models\Country::query()->pluck('name', 'id');
                });

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
        $grid->disableRowSelector();

        $grid->model()->collection(function (Collection $collection) {
            $allIds = $collection->flatMap(function ($row) {
                return array_filter(explode(',', (string) $row->microphone));
            })->unique()->values()->all();

            // fetch all needed users once
            $users = collect();
            if (!empty($allIds)) {
                $users = User::select(['id', 'name'])
                    ->with('profile:id,user_id,avatar')
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
            $path = @$this->room_cover;
            $id = @$this->id;
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

            $showUrl = url("areaManager/rooms/{$id}");
            return "
                <a href='{$showUrl}' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        <img src='$url' alt='Room Image' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;'>
                        <div>
                            <span style='cursor: pointer;'>$encodedName</span><br>
                            <span style='cursor: pointer;'>ID: $id</span>
                        </div>
                    </div>
                 </a>
            ";
        });

        $grid->column('owner_id', __('room owner'))->display(function ($name) {
            $user = $this->owner;
            if (! $user) {
                return __('No User');
            }
             $showUrl = url("areaManager/users/profile/{$this->id}");
            return app(UserService::class)->adminUserCard($user, withoutLevels: true, showUrl: $showUrl);
        });

        $grid->column('session', __('Gifts'))->display(function () {
            return $this->session  ?? 0;
        });


        $grid->column('id', __('Number of users'))->display(fn() => $this->room_visitors_count ?? 0);
    }











    public function updatePinStatus($id, Request $request)
    {
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

        $form->display(__('ID'));
        $form->text('numid', __('numid'));
        $form->switch('room_status', __('room status'))->options(Common::getSwitchStates());
        $form->switch('is_top', __('top room (API sort)'))->options(Common::getSwitchStates());
        $form->switch('pin', __('pin'))->options(Common::getSwitchStates());
        $form->text('max_admin', __('max admin'));
        $form->text('room_name', __('room name'));
        $form->image('room_cover', __('room cover'));
        $form->text('room_intro', __('room intro'));
        $form->text('room_pass', __('room pass'))->rules('nullable|integer|digits:6');
        $form->hidden('is_afk', __('owner in'));
        $form->hidden('type', __(' '))->default('live');
        $form->select('room_class')->options(function () {
            $options = [];
            $cats = RoomCategory::query()->where('enable', 1)->where('parent_id', 0)->get();
            foreach ($cats as $cat) {
                $options[$cat->id] = $cat->name;
            }
            return $options;
        });
        $form->select('room_type', __('room type'))->options(function () {
            $options = [];
            $cats = RoomCategory::query()->where('enable', 1)->where('parent_id', $this->room_class)->get();
            foreach ($cats as $cat) {
                $options[$cat->id] = $cat->name;
            }
            return $options;
        });
        $form->text('room_welcome', __('room welcome'));
        $form->number('sort_num', __('Sort Num'));


        return $form;
    }

    public function removeAdmin(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);
        $adminId = $request->admin_id;

        $admin = AdminModel::find($adminId);

        $admins = array_filter(explode(',', $room->room_admin));

        $admins = array_diff($admins, [$adminId]);

        $room->room_admin = implode(',', $admins);
        $room->save();

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
            $avatar = $user->profile?->avatar ?? null;
            $user->avatar_url = getImagePath($avatar);
            return $user;
        });

        return response()->json($users);
    }

    public function updateBasicInfo(Request $request, $id)
    {
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
