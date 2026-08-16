<?php

namespace App\Admin\Controllers;

use App\Models\MomentGallery;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use App\Models\Country;
use Modules\Vip\Entities\UserVip;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use App\Facades\UserHandling;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use App\Admin\Services\UserService;
use App\Admin\Widgets\InfoBox;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use App\Admin\Widgets\Table as TableWidget;
use Illuminate\Validation\Rule;
use App\Admin\Forms\ProfileForm;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Admin\Selectable\ImageColors;
use App\Admin\Actions\DeletePackAction;
use Illuminate\Support\Facades\Session;
use App\Admin\Actions\ChangeAgencyAction;
use App\Admin\Actions\KickOfAgencyAction;
use App\Admin\Actions\KickOfFamilyAction;
use App\Admin\Actions\DeleteUserVipAction;
use App\Admin\Actions\EditPackExpireAction;
use Encore\Admin\Auth\Permission;
use Modules\Moment\Entities\Moment;
use Modules\Reals\Entities\Real;
use Modules\SwitchAccount\Entities\UserAccount;
use Modules\Achievement\Http\Services\UserAchievementService;
// use Encore\Admin\Actions\Response;

class FreeUserController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'free-users';
    public $hiddenColumns = [
        'is_host',
        'status',
        'is_gold_id',
        'id',
        'email',
        'phone',
        'di',
        'gold',
        'coins',
        'actions'
    ];
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Users';
    }

    public function index0(Content $content)
    {
        // if (!Admin::user()->can('*')) {
        //     Permission::check('browse-users');
        // }


        $forms = [
            'one' => ProfileForm::class,
            'tow' => ProfileForm::class,
        ];

        return $content
            ->title(__($this->title))
            ->body(Tab::forms($forms));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__($this->title))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__($this->title))
            ->body($this->form()));
    }

    public function index(Content $content)
    {
        // if (!Admin::user()->can('*')) {
        //     Permission::check('browse-users');
        // }

        return $content
            ->title(__($this->title))
            ->row(function (Row $row) {
                $row->column(12, $this->grid2());
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
    }

    protected function grid2()
    {
        $transfer_salary = settings()->get('transfer_salary');
        $stop_invite_code = settings()->get('stop_invite_code');
        $stop_charge = Common::getSettingValue('stop_charge') ?? 0;
        $make_rooms_top = settings()->get('make_rooms_top');
        // The blade also reads these — were never passed, so the page fataled with
        // "Undefined variable $make_gift_top" (caught on Meow test smoke 2026-06-20).
        $make_gift_top = settings()->get('close_open_gifts');
        $change_country = settings()->get('change_country');
        $register_account = (int) (Common::getSettingValue('register_account') ?? 3);
        return (new Box(
            title: __('admin.Actions'),
            content: view('admin.grid.users.userChargeViewNew', compact(['stop_charge', 'make_rooms_top', 'stop_invite_code', 'transfer_salary', 'make_gift_top', 'change_country', 'register_account'])),
        ))->collapsable()->class('box collapsed-box');
    }
    protected function grid()
    {
        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);
        $grid->model()->with("ownerRoom");
        $grid->model()->with(['targets' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        $grid->model()->where('type_user', 0)->where('is_host', 0)->where(function ($query) {
            $query->whereNull('family_id')->orWhere('family_id', 0);
        });

        // if (request()->online == 1) {
        //     $grid->model()->where('online_time', '>=', now()->startOfDay()->timestamp)->where('online_time', '<=', now()->timestamp);
        // } else if ($haveCoins) {
        //     $grid->model()->where('di', '>', 0)->orderByDesc('di');
        // } else {
        $grid->model()->orderByDesc('id');
        // }
        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('UserVip.vip_id', __('vip'))->select(Common::by_ovip_filter());

                $filter->column(1 / 2, function ($filter) {
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%")->orWhere('special_id', 'like', "%$input%")->orWhere('nickname', 'like', "%$input%")->orWhere('email', 'like', "%$input%");
                    }, __('User'))->placeholder(__('Search by name , UUID , nickname and email'));
                });
            });
        });
        $grid->column('id', __('Id'));
        if ($haveCoins) {
            $grid->column('di', __('coins'))->display(function ($value) {
                return number_format($value);
            });
        }

        $grid->column('uuid', __('uuid'))->display(function () {
            return $this->uuid == $this->original_uuid
                ? __("uuid") . ' : ' . $this->uuid
                : __("uuid") . ' : ' . $this->uuid . '<br>' . __("special uuid") . ' : ' . $this->original_uuid;
        });

        $grid->column('name', __('user'))
            ->display(function () {
                return app(UserService::class)->adminUserCard($this);
            });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
//        $grid->column('return', __('status user'))->display(function () {
//            $userSetting = $this->userSetting ?? (object) ['show_invite_code' => 0, 'hide_chat' => 0];
//            return (new \App\Admin\Actions\UserAction(
//                $this->id,
//                $this->charge_status,
//                $this->transfer_salary,
//                $userSetting->show_invite_code,
//                $userSetting->hide_chat,
//                $this->can_play
//            ))->render();
//        });


        $grid->column('reals.user_id', __('user Active'))->modal(__('user Active'), function ($model) {

            $results = [
                __('reel count') => $this->reals()->count() ?? 0,
                __('moment_count') => $this->moments()->count() ?? 0,
                __('total_days') => $this->total_days ?? 0,
                __('total_hours') => $this->liveTime->sum("hours") ?? 0,
            ];

            return new Table([__('Field Name'), __('Value')], $results);
        });

        $grid->column('phone', __('Phone'));
        // $grid->column('total_charge_level', __('admin.charge_level'));
        $grid->column('target', __('target'))->expand(function ($model) {

            $agencyId = $model->agency_id;
            $targets = $model->targets
                ->filter(fn ($target) => $agencyId !== null && $target->agency_id == $agencyId)
                ->sortByDesc('created_at')
                ->values()
                ->map(function ($target) {
                $data = json_decode($target->extras, true);

                $moment_upload = $data['moment']['upload'] ?? '';
                $moment_likes = $data['moment']['likes'] ?? '';
                $moment_comments = $data['moment']['comments'] ?? '';

                // For "reel"
                $reel_upload = $data['reel']['upload'] ?? '';
                $reel_likes = $data['reel']['likes'] ?? '';
                $reel_comments = $data['reel']['comments'] ?? '';

                // Combine moment fields
                $moment_info = "Upload: {$moment_upload} | Likes: {$moment_likes} | Comments: {$moment_comments}";

                // Combine reel fields
                $reel_info = "Upload: {$reel_upload} | Likes: {$reel_likes} | Comments: {$reel_comments}";
                $target =
                    [
                        'id' => $target->id,
                        'add_month' => $target->add_month . '/' . $target->add_year,
                        'target_usd' => $target->target_usd,
                        'target_agency_share' => $target->target_agency_share,
                        'user_diamonds' => $target->user_diamonds,
                        'user_hours' => $target->user_hours,
                        'user_days' => $target->user_days,
                        'moment' => $moment_info,
                        'real' => $reel_info,
                        'user_obtain' => $target->user_obtain,
                        'updated_at' => $target->updated_at,
                    ];


                return $target;
            });

            return new \App\Admin\Widgets\Table(
                [
                    'ID',
                    __('month') . '/' . __('year'),
                    __('usd') . ' ' . __('deserved') . '(%)',
                    __('agency share') . '(%)',
                    __('user diamonds'),
                    __('user hours'),
                    __('user days'),
                    __('moment'),
                    __('real'),
                    __('user obtain'),
                    __('at time'),
                ],
                $targets->toArray()
            );
        });
        Admin::style('.btn-circle {width: 30px; height: 30px; font-size:15px; border-radius: 50%; text-align: center; }');
        $deviceCounts = [];
        $grid->model()->collection(function ($collection) use (&$deviceCounts) {
            $tokens = $collection->pluck('device_token')
                ->filter(fn ($t) => !is_null($t) && $t !== '')
                ->unique()
                ->values();
            if ($tokens->isNotEmpty()) {
                $deviceCounts = User::whereIn('device_token', $tokens->all())
                    ->whereNotNull('device_token')
                    ->groupBy('device_token')
                    ->selectRaw('device_token, COUNT(*) as aggregate')
                    ->pluck('aggregate', 'device_token')
                    ->all();
            }
            return $collection;
        });
        $grid->column('custom_button2', __('Accounts number'))->display(function () use (&$deviceCounts) {
            $device_token = $this->device_token;
            if (is_null($device_token) || $device_token === '') {
                return 0;
            }
            return $deviceCounts[$device_token] ?? 0;
        })->modal(__('Other Accounts on the same device'), function ($model) {
            $device_token  = $this->device_token;
            $users         =
                User::select("name", 'uuid', 'phone')->where('device_token', $device_token)->where('device_token', '!=', null)->get();
            $filteredUsers = $users->map(function ($user) {
                return $user->only(["name", "uuid", "phone"]);
            });
            return new Table([__('Name'), __('uuid'), __('phone')], $filteredUsers->toArray());
        });

        $grid->column('achievements', __('achievements'))->modal(__('achievements'), function ($model) {
            $achivement      = new UserAchievementService();
            $data_achivement = $achivement->getUserAchievement($model);

            $filtered = $data_achivement->map(function ($user) {

                $img = $user["valid_image"] ?? $user["custom_image"];
                $img = getDriverUrl() . '/' . $img;
                $img = "<img src='" . $img . "' style='width:50px;height:50px' class='img img-thumbnail'$ />";
                //                $user->only(["user_achievement_levels.id","achievement_levels.valid_image"]);
                return [
                    'id'     => $user['id'],
                    'target' => $user['target'],
                    'image'  => $img,
                ];
            });
            return (new Table([__('Id'), __('target'), __('image')], $filtered->toArray()));
        });

        $grid->column('custom_button3', __('Change account'))->modal('حسابات اخري علي نفس الجهاز', function ($model) {
            $device_token  = $this->device_token;
            $users = UserAccount::where('device_token', $device_token)->get();
            $parentUserIds = $users->pluck('parent_user_id');
            $childUserIds = $users->pluck('child_user_id');

            $allIds = $parentUserIds->merge($childUserIds)->unique()->values()->all();
            $userId = $this->id;
            $filteredIds = array_filter($allIds, function ($id) use ($userId) {
                return $id != $userId;
            });
            $filteredIds = array_values($filteredIds);
            $users = User::query()->whereIn('id', $filteredIds)->select("name", 'uuid', 'phone')->get();
            $filteredUsers = $users->map(function ($user) {
                return $user->only(["name", "uuid", "phone"]);
            });
            return new Table([__('Name'), __('uuid'), __('phone')], $filteredUsers->toArray());
        });


        $grid->disableExport();
        $appEnv = config('app.env');
        if ($appEnv == 'production') $grid->disableCreateButton();

        $this->extendGrid($grid);
        $grid->actions(function ($actions) {
            $model = $actions->row;

            $actions->add(new class extends \Encore\Admin\Actions\RowAction {
                public $name = 'Status User';

                public function render()
                {
                    $model = $this->row;
                    $userSetting = $model->userSetting ?? (object) ['show_invite_code' => 0, 'hide_chat' => 0];
                    return (new \App\Admin\Actions\UserAction(
                        $model->id,
//                        $model->charge_status,
                        $model->transfer_salary,
//                        $userSetting->show_invite_code,
//                        $userSetting->hide_chat,
//                        $model->can_play
                    ))->render();
                }
            });

            if ($model->agency_id >= 1) {
                $actions->add(new KickOfAgencyAction());
            }
            if ($model->family_id >= 1) {
                $actions->add(new KickOfFamilyAction());
            }
            if ($model->agency_id >= 1) {
                $actions->add(new ChangeAgencyAction($model->id));
            }
        });


        return $grid;
    }

    public function stop_charge(Request $request)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $value = $request->stop_charge == "false" ? "0" : "1";

        \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set("stop_charge", $value));
    }

    public function make_rooms_top(Request $request)
    {
        if ($request->make_rooms_top == "true") {
            settings()->set("make_rooms_top", "1");
        } else {
            settings()->set("make_rooms_top", "0");
        }
    }

    public function transferSalary(Request $request)
    {
        if ($request->transfer_salary == "true") {
            settings()->set("transfer_salary", "1");
        } else {
            settings()->set("transfer_salary", "0");
        }
    }

    public function show($id, Content $content)
    {
        return $content->row(function ($row) use ($id) {
            $user = User::findOrFail($id);

            $type = $user->type_user;
            $userType = match ($type) {
                0 => __("User"),
                1 => __("Host"),
                2 => __("Host Agent"),
                3 => __("Shipping Agent"),
                4 => __("Resort & Shipping Agent"),
                5 => __("Admin"),
                default => $type,
            };

            // Info Boxes
            $row->column(12, function ($column) use ($user, $userType) {
                $column->row(view('admin.grid.users.show', compact('user')));

                $column->row(function ($row) use ($user, $userType) {
                    $row->column(2, new InfoBox($user->salary, 'dollar', 'green', '?type=balance_details', __('Balance')));
                    $row->column(2, new InfoBox(Common::level_center($user)['sender_level'], 'dollar', 'orange', '?type=balance_details', __('Level')));
                    $row->column(2, new InfoBox(Common::level_center($user)['receiver_level'], 'dollar', 'blue', '?type=balance_details', __('Worth')));
                    $row->column(2, new InfoBox($user->getTotalDiamond(), 'dollar', 'red', '?type=balance_details', __('Diamonds')));
                    $row->column(2, new InfoBox($user->di, 'dollar', 'yellow', '?type=balance_details', __('Coins')));
                    $row->column(2, new InfoBox($userType ?? '', '', 'green', '?type=balance_details', __('Type')));
                });
            });

            $row->column(12, function ($column) use ($id) {
                $tab = new Tab();

                Admin::style('
                            .nav-tabs-custom {
                                background: transparent !important;
                                box-shadow: none !important;
                                border: none !important;
                            }
                            .nav-tabs-custom>.nav-tabs {
                                background: transparent;
                                border: none;
                                display: flex;
                                padding: 0;
                                margin: 0;
                                width: 100%;
                            }
                            .nav-tabs-custom > .nav-tabs > li {
                                flex: 1;
                                border: none;
                                margin: 0;
                                padding: 0 2px;
                            }
                            .nav-tabs-custom > .nav-tabs > li:first-child {
                                padding-left: 0;
                            }
                            .nav-tabs-custom > .nav-tabs > li:last-child {
                                padding-right: 0;
                            }
                            .nav-tabs-custom > .nav-tabs > li > a {
                                background: #1e1e1e;
                                color: white;
                                padding: 8px 24px;
                                border-radius: 4px;
                                margin: 0;
                                border: none;
                                font-size: 14px;
                                text-align: center;
                                width: 100%;
                                display: block;
                            }
                            .nav-tabs-custom > .nav-tabs > li.active > a {
                                background: #ff9800;
                                color: white;
                                border: none;
                            }
                            .nav-tabs-custom > .nav-tabs > li > a:hover {
                                background: #ff9800;
                                color: white;
                                border: none;
                            }
                            .nav-tabs-custom>.tab-content {
                                background: transparent;
                                border: none;
                                padding: 10px 0;
                            }
                           .nav-tabs-custom > .nav-tabs > li.pull-right.header {
                                display: none !important;
                            }

                            .nav-tabs-custom > .nav-tabs > li.pull-right {
                                display: none !important;
                            }
                        ');
                $tab->add(__('Packs'), $this->packList($id)->render());
                $tab->add(__('vips'), $this->vipList($id)->render());
                $tab->add(__('Reals'), $this->realList($id)->render());
                $tab->add(__('moments'), $this->momentList($id)->render());

                $column->append($tab);
            });
        });
    }

    protected function packList($id)
    {
        Pack::query()->where('expire', '!=', 0)->where('expire', '<', time())->delete();
        $grid = new Grid(new Pack);
        $grid->model()->where('user_id', $id);
        $grid->id('ID');
        $grid->column('user_id', __('user id'));
        $grid->column('get_type', __('get type'))->using(
            [
                1 => __('vip level automatic acquisition'),
                2 => __('activities'),
                3 => __('treasure box'),
                4 => __('purchase'),
                5 => __('background addition'),
            ]
        );
        $grid->column('type', __('type'))->using(
            [
                1  => trans('Gemstone'),
                3  => trans('Card Scroll'),
                4  => trans('Avatar Frame'),
                5  => trans('Bubble Frame'),
                6  => trans('Entering Special Effects'),
                7  => trans('Microphone Aperture'),
                8  => trans('Badge'),
                9  => trans('NoKick'),
                10 => trans('Icon'),
                11 => trans('intro animation'),
                12 => trans('wapel'),
                13 => trans('hide country'),
                14 => trans('vip gifts'),
                15 => trans('no pan'),
                16 => trans('hidden room'),
                17 => trans('anonymous man'),
                18 => trans('colored name'),
                19 => trans('profile visitors hide in'),
                20 => trans('hide last active'),
                21 => trans('sound effect'),
                22 => trans('upload GIF image'),
            ]
        );
        $grid->column('target_id', __('img'))->display(function () {
            $ware = Ware::query()->where('id', $this->target_id)->value('show_img');
            $src  = getDriverUrl() . '/' . $ware;
            return "<img width='30' src='$src'>";
        });
        $grid->column('expire', __('expire'))->display(function ($row) {
            if ($this->expire) {
                return Carbon::createFromTimestamp($this->expire)->format('Y-m-d H:i:s');
            }
            return __('no time');
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            $actions->add(new DeletePackAction());
            $actions->add(new EditPackExpireAction());
        });

        $grid->disablePagination();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }

    protected function vipList($id)
    {
        // UserVip::query ()->where ('expire','!=',0)->where ('expire','<',time ())->delete ();

        $grid = new Grid(new UserVip());
        $grid->model()->where('user_id', $id);
        $grid->id('ID');
        $grid->column('user_id', __('user id'));
        $grid->column('level', __('level'));
        $grid->column('expire', __('expire'))->display(function ($row) {
            if ($this->expire) {
                return Carbon::createFromTimestamp($this->expire)->format('Y-m-d H:i:s');
            }
            return __('no time');
        });
        $grid->column('qty', __('qty'));
        $grid->column('total', __('total Price'));
        // $grid->column('price', __('price'));
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            $actions->add(new DeleteUserVipAction());
            //            $actions->add(new EditPackExpireAction());
        });

        $grid->disablePagination();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }

    protected function realList($userId): Grid
    {
        $grid = new Grid(new Real());

        $grid->model()->where('user_id', $userId)->orderByDesc('created_at');

        $grid->column('comment_num', __('Status'))->display(function () {
            $like = count(@$this->likes);
            $commentNum = count(@$this->comments);
            return "<span class=\"fa fa-comment\"> $commentNum</span>  <span class=\"fa fa-thumbs-up\"> $like</span> ";
        });

        $grid->column('created_at', __('Created at'))->sortable()->diffForHumans();

        $grid->column('video', __('Video'))->display(function () {
            $videoPath = getDriverUrl().'/'.$this->url;
            return "<video width='150' height='100' controls><source src='$videoPath' type='video/mp4'>Your browser does not support the video tag.</video>";
        });

        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 20) . (strlen($description) > 30 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });

        Admin::script("
    $(document).ready(function () {
        $('.view-description').click(function (e) {
            e.preventDefault();

            var description = $(this).data('description');

            $('#modalDescriptionTitle').text('Full Description');
            $('#modalDescriptionContent').text(description);

            $('#descriptionModal').modal('show');
        });
    });");

        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
        });
        $grid->disableExport();
        $grid->disableFilter();

        return $grid;
    }

    protected function momentList($userId): Grid
    {
        $grid = new Grid(new Moment());

        // Filter the Moment records by the given user ID
        $grid->model()->where('user_id', $userId)->orderByDesc('created_at');

        // Display description with modal
        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });

        // Display comments and likes
        $grid->column('comment_num', __('Status'))->display(function () {
            $likeCount = count(@$this->likes);
            $commentCount = count(@$this->comments);
            return "<span class=\"fa fa-comment\"> $commentCount</span>  <span class=\"fa fa-thumbs-up\"> $likeCount</span>";
        });

        // Display creation date
        $grid->column('created_at', __('Created at'))->sortable()->diffForHumans();

        // Display images
        $grid->column('img', __('Image'))->display(function () {
            $id = $this->id;
            $galleries = MomentGallery::where('moment_id', $id)->get();

            if ($galleries->isEmpty()) {
                return 'No Image';
            }

            $html = '<div id="image-gallery-' . $id . '" style="display: none;">';

            foreach ($galleries as $image) {
                $imgUrl = getDriverUrl() . '/' . $image->image;

                $html .= '<img src="' . $imgUrl . '"
                         style="width: 100%; height: 200px; object-fit: cover;"
                         data-original="' . $imgUrl . '"
                         loading="lazy"
                         class="gallery-image">';
            }

            $html .= '</div>';

            // Show only the first image
            $firstImageUrl = asset("images/moment.jpg");

            $html .= '<img src="' . $firstImageUrl . '"
                      style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
                      onclick="document.querySelector(`#image-gallery-' . $id . ' img`).click()">';

            Admin::script("
            new Viewer(document.getElementById('image-gallery-$id'));
        ");

            return $html;
        });
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
        });
        $grid->disableExport();
        $grid->disableFilter();

        return $grid;
    }

    public function showAdditionalInfo($id, Content $content)
    {
        return $content
            ->row(function (Row $row) {
                $row->column(12, $this->showColSearch());
            })
            ->row(
                function ($row) use ($id) {
                    $user = User::findOrFail($id);
                    $type = $user->type_user;
                    switch ($type) {
                        case 0:
                            $userType = __("User");
                            break;
                        case 1:
                            $userType = __("Host");
                            break;
                        case 2:
                            $userType = __("Host Agent");
                            break;
                        case 3:
                            $userType = __("Shipping Agent");
                            break;
                        case 4:
                            $userType = __("Resort & Shipping Agent");
                            break;
                        case 5:
                            $userType = __("Admin");
                            break;
                        default:
                            $userType = $type; // Keep the original value if no match is found
                            break;
                    }

                    $row->column(2, new InfoBox(__('Balance'), 'dollar', 'green', '?type=balance_details', $user->salary));
                    $row->column(2, new InfoBox(__('Level'), 'dollar', 'orange', '?type=balance_details', Common::level_center($user)['sender_level']));
                    $row->column(2, new InfoBox(__('worth'), 'dollar', 'blue', '?type=balance_details', Common::level_center($user)['receiver_level']));
                    $row->column(2, new InfoBox(__('diamonds'), 'dollar', 'red', '?type=balance_details', $user->getTotalDiamonds()));
                    $row->column(2, new InfoBox(__('coins'), 'dollar', 'red', '?type=balance_details', $user->di));
                    $row->column(2, new InfoBox(__('type'), 'dollar', 'red', '?type=balance_details', $userType));
                }
            );
    }

    protected function showColSearch()
    {
        $form = new Box();
        $form->view('admin.grid.users.userChargeView');

        return $form;
    }

    public function update($id)
    {

        unset(request()['level']);
        unset(request()['worth']);

        return $this->form()->update($id);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        $form = new Form(new User());
        if ($form->isEditing()) {
            $userId           = request()->segment(3);

            $user             = User::findOrFail($userId);
            $oldDiValue       = $user->getOriginal('di');
            $oldDiamoundValue = $user->getOriginal('user_diamond');
        } else {
            $oldDiValue       = null;
            $oldDiamoundValue = null;
        }


        $loggedInUserId = Admin::user()->id;
        $form->display('id', __('id'));
        if (!$form->isEditing()) {
            // Add a hidden field for 'uuid' in the edit form
            $form->text('uuid', __('uuid'))->creationRules([
                'required',
                Rule::unique('users', 'uuid'),
                function ($attribute, $value, $fail) {
                    if (DB::table('wares')->where('value', $value)->exists()) {
                        return $fail(__('لا يمكنك استخدام معرف المميز هذا'));
                    }
                }
            ])
                ->updateRules([
                    'required',
                    Rule::unique('users', 'uuid')->ignore(request()->route('id')),
                    // نفس الشيء هنا مع التحقق من عدم وجود القيمة في جدول wares
                    function ($attribute, $value, $fail) {
                        if (DB::table('wares')->where('value', $value)->exists()) {
                            return $fail(__('القيمة موجودة بالفعل في جدول wares.'));
                        }
                    }
                ]);
        }

        $form->belongsTo('image_color_id', ImageColors::class, __('Color'));


        $form->text('name', __('Name'));
        if ($form->isEditing()) {
            $form->hidden('oldDiValue')->default($oldDiValue);
            $form->hidden('oldDiamoundValue')->default($oldDiamoundValue);
        }
        $form->text('uuid', __('uuid'))->updateRules(['required', "unique:users,uuid,{{id}}"]);

        $form->image('profile.avatar', __('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        $form->image('profile.image_id', __('image Id'));
        $state = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];

        $form->switch('charge_status', __("charge status"))->states($state);
        $form->switch('transfer_salary', __("transfer_salary"))->states($state);
        $form->switch('userSetting.show_invite_code', __("show invite code"))->states($state);
        $form->switch('userSetting.hide_chat', __("hide_chat"))->states($state);
        $form->select('country_id', trans('country'))->options(function () {
            $ops       = [null => __('no country')];
            $countries = Country::all();
            foreach ($countries as $country) {
                $ops[$country->id] = App::isLocale('en') ?  ($country->e_name ?? $country->name) : $country->name;
            }
            return $ops;
        });
        $states = [
            'default'  => ['value' => 0, 'text' => 'yes', 'color' => 'success'],
            'on'  => ['value' => 2, 'text' => 'yes', 'color' => 'success'],
            'off' => ['value' => 3, 'text' => 'no', 'color' => 'danger'],
        ];
        if ($form->isCreating()) {
            $form->switch('can_play', __('canPlay'))->default(0)->states($states);
        } elseif ($form->isEditing()) {
            $form->switch('can_play', __('canPlay'))->value(function ($can_play) {
                $can_play = UserHandling::chickLevelToPlay($this);
                return $can_play ? 'on' : 'off';
            })->states($states);
        }

        if ($loggedInUserId == 1 || $loggedInUserId == 2) {
            $form->number('di', __('Coins'))->default(0)->disable();

            $form->number('user_diamond', __('Diamonds'))->default(0);
            $form->number('total_sender_level', __('Sender Level'))->default(0);
            $form->number('total_received_level', __('Received Level'))->default(0);
            $form->number('total_charge_level', __('admin.charge_level'))->default(0);
            $form->number('salary', __('salary'))->disable();
        }
        $form->select('profile.gender', __('gender'))->options([0 => __('female'), 1 => __('male')]);

        $form->email('email', __('Email'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly');
        $form->password('password', __('Password'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly')->creationRules('required');
        $form->text('phone', __('phone'))->creationRules(['required', "unique:users,phone,{{id}}"])->updateRules(['required', "unique:users,phone,{{id}}"]);


        $form->switch('status', __('block status'))->options(Common::getSwitchStates2());
        $form->select('type_user', trans('User Type'))->options([
            $form->model()->type_user => $form->model()->type_user,
            0                         => 'مستخدم',
            1                         => 'مضيف',
            2                         => 'وكيل مضيفين',
            3                         => 'وكيل شحن',
            4                         => ' وكيل مصيفين ووكيل شحن',
            5                         => 'اداري',

        ])->default(0);



        if (Session::has('show_alert')) {
            $form->html('<script>
            $(document).ready(function () {
                alert(" يملك هذا المستخدم وكالة   . الرجاء مسح الوكالة واخراج المضيفين اولا قبل تغيير نوع المستخدم");
            });
        </script>');
        }

        $form->saving(function (Form $form) use ($oldDiValue, $oldDiamoundValue) {
            $type_user = request()->type_user;
            $model     = $form->model();
            $user_id   = $model->id;
            if ($form->oldDiValue != $oldDiValue) {
                $form->di = $oldDiValue;
            }

            if ($form->oldDiamoundValue != $oldDiamoundValue) {
                $form->user_diamond = $oldDiamoundValue;
            }

            $agancy = Agency::where('app_owner_id', $user_id)->first();
            if ($agancy) {


                if (in_array(intval($type_user), [0, 1, 5]) && $model->isDirty('type_user')) {
                    session()->flash('show_alert', 'Your alert message');
                    return redirect()->back();
                }


                switch ($type_user) {


                    case 2:
                        User::where('id', $user_id)->update(['type_user' => 2]);
                        break;
                    case 3:
                        User::where('id', $user_id)->update(['type_user' => 3]);
                        break;
                    case 4:
                        User::where('id', $user_id)->update(['type_user' => 4]);
                        break;

                    default:

                        break;
                }
            }
        });


        return $form;
    }




    public function request_invite_code(Request $request)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $value = $request->stop_invite_code == "true" ? "1" : "0";

        \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set("stop_invite_code", $value));
    }
}
