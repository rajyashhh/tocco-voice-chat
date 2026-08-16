<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Models\Config;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\GiftLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserTarget;
use App\Services\AppFeatureService;
use Carbon\Carbon;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class FamilyController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;

    public $permission_name = 'families';
    public $hiddenColumns = [];

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("families");
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('families'))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('families'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('families'))
            ->body($this->form()));
    }

    // public function show($id, Content $content)
    // {
    //     return parent::show($id, $content
    //         ->title(trans('families'))
    //         ->body($this->detail($id)));
    // }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Family);
        $countryID = Common::filterCountryIds();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->equal('id', __('ID'));
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('owner', function ($subQuery) {
                        $subQuery->where('uuid', 'like', "%{$this->input}%");
                    });
                }, __('UUID'), 'uuid')->placeholder(__('search for host by UUID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($date = request('date')) {
                        $dateEn = Carbon::parse(convertArabicToEnglishNumbers($date))->endOfDay();
                        $query->whereDate('created_at', $dateEn);
                    }
                }, __('created_at'), 'date')->date();
            });
        });
        $grid->model()
            ->select(['id', 'name', 'image', 'user_id', 'num', 'total_diamond', 'created_at'])
            ->when($countryID, fn($q) => $q->whereHas('owner', fn($q) => $q->whereIn('country_id', $countryID)))
            ->withCount([
                'allMembers as members_count_cached',
                'admins as admins_count_cached'
            ])
            ->with([
                'owner:id,name,uuid',
                'owner.profile:id,user_id,avatar',
                'owner.country',
                'owner.senderLevel',
                'owner.receiverLevel',
                'owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            ])
            ->orderByDesc('id');

        $grid->column('image', __('family'))->display(function ($image) {
            $name = mb_convert_encoding($this->name, 'UTF-8', 'UTF-8');

            if (mb_strlen($name) > 50) {
                $name = mb_substr($name, 0, 50) . ' ...';
            }

            if (strlen($name) > 50) {
                $name = substr($name, 0, 50) . ' ...';
            }

            $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
            $encodedName = htmlspecialchars($cleanName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $defaultImage = asset("images/family.jpg");
            $url = $image ? getImagePath($image) : $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $imgTag = handleShowImageWithTypes($this->id, $url, 40, 40);

            $familyUrl = url("admin/families/{$this->id}");
            $id = $this->id;

            return "
                <a href='{$familyUrl}' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        {$imgTag}
                        <div>
                            <span style='cursor: pointer;'>{$encodedName}</span><br>
                            <span style='cursor: pointer;'>ID: {$id}</span>
                        </div>
                    </div>
                </a>
            ";
        });

         $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('num', __('number of people'))->display(function ($value) {
            // Original accessor returns count - 1 to exclude owner
            $count = max(0, ($this->members_count_cached ?? 0) - 1);
            return $count . '/' . $value;
        });
        $grid->column('num_admins', __('number of admins'))->display(function ($value) {
            return $this->admins_count_cached . '/' . $value;
        });
        $grid->column('max_level', __('level'));
        $grid->column('max_exp', __('exp'));
        $grid->column('created_at', __('created_at'));

        $grid->tools(function (Grid\Tools $tools) {
            $uuid = request('uuid') ?? (request('owner')['uuid'] ?? null);
            $query = http_build_query([

                'date' => request('date') ? convertArabicToEnglishNumbers(request('date')) : '',
                'id' => request('id'),
                'uuid' => $uuid,
            ]);

            $tools->append('<a href="' . url('/admin/families-excel') . '?' . $query . '" target="_blank" class="btn btn-sm btn-success">
                <i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
        });
        $this->extendGrid($grid);
        $grid->disableExport();
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Content
     */
    // protected function detail($id)
    // {
    //     $show = new Show(Family::findOrFail($id));

    //     $show->id(__('ID'));
    //     //        $show->is_success('is_success');
    //     $show->image(__('image'));
    //     $show->name(__('name'));
    //     $show->introduce(__('introduce'));
    //     $show->notice(__('notice'));
    //     $show->num(__('number of people'));
    //     $show->user_id(__('user id'));
    //     $show->speakswitch(__('speak switch'));
    //     $show->status(__('status'));
    //     //        $show->update_user_id('update_user_id');
    //     //        $show->suctime('suctime');
    //     //        $show->start_time('start_time');
    //     //        $show->created_at(trans('admin.created_at'));
    //     //        $show->updated_at(trans('admin.updated_at'));
    //     $this->extendShow($show);
    //     return $show;
    // }

    public function show($id, Content $content)
    {
        $type = is_array(request('type')) ? null : request('type');
        $year = request('year') ?? Carbon::now()->year;
        $month = request('month') ?? Carbon::now()->month;

        $family = Family::with(['owner:id,name,uuid', 'owner.profile:id,user_id,avatar'])
            ->findOrFail($id);

        $familyLevel = $family->level;

        $familyMembers = FamilyUser::where('family_id', $family->id)
            ->where('status', 1)
            ->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar'])
            ->when($type !== null, fn($q) => $q->where('user_type', $type))
            ->orderByDesc('user_type')
            ->paginate(10, ['*'], 'member_page');

        $familyUserIds = FamilyUser::where('family_id', $family->id)
            ->where('status', 1)
            ->pluck('user_id');

        $memberTargets = User::whereIn('id', $familyUserIds)
            ->whereHas('targets', function ($query) use ($family, $month, $year) {
                $query->where('add_month', $month)
                    ->where('add_year', $year);
            })
            ->with(['targets' => function ($query) use ($family, $month, $year) {
                $query->where('add_month', $month)
                    ->where('add_year', $year);
            }, 'profile:id,user_id,avatar'])
            ->paginate(10, ['*'], 'target_page');

        return parent::show($id, $content->title(__('family profile'))
            ->view('family_profile', compact('family', 'familyMembers', 'familyLevel', 'memberTargets', 'month', 'year')));
    }

    public function kickMember($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('delete-' . $this->permission_name);
        }

        $familyUser = FamilyUser::findOrFail($id);

        if ($familyUser->user_type == 2) {
            return response()->json([
                'status' => false,
                'message' => __('This User is the host Of family can\'t delete it go to remove family first'),
            ], 403);
        }

        User::where('id', $familyUser->user_id)->update(['family_id' => null]);
        $familyUser->delete();

        return response()->json([
            'status' => true,
            'message' => __('done'),
        ]);
    }

    public function toggleAdmin($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $familyUser = FamilyUser::findOrFail($id);

        if ($familyUser->user_type == 2) {
            return response()->json([
                'status' => false,
                'message' => __('Cannot change the owner role'),
            ], 403);
        }

        $familyUser->user_type = $familyUser->user_type == 1 ? 0 : 1;
        $familyUser->save();

        return response()->json([
            'status' => true,
            'message' => __('done'),
        ]);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Family);
        $this->disableFormTools($form);

        if ($form->isEditing()) {
            $form->display('id', __('ID'));
        }
        $form->text('name', __('name'))->rules('required');
        $form->text('introduce', __('introduce'))->rules('required');
        $form->text('notice', __('notice'))->rules('required');
        $form->image('image', __('image'));
        $form->text('num', __('number of people'))->rules('required|integer|max:10000')->default('20');
        $form->select('user_id', __('user id'))->options(function ($value) {
            $ops2 = [];
            foreach (User::Where('id', $value)->get() as $user) {
                $ops2[$user->id] = $user->uuid . '_' . $user->name;
            }
            return $ops2;
        })->ajax('/api/search/users4', 'id', 'name')->rules('required');
        $form->hidden('is_success', 'is_success')->default(1)->rules('required');

        $form->saving(function (Form $form) {
            $oldOwnerFamily = $form->model()->user_id;
            $newOwnerFamily = request()->user_id;
            if ($form->model()->exists && ($oldOwnerFamily != $newOwnerFamily)) {
                User::where('id', $form->model()->user_id)->update(['family_id' => 0]);
                FamilyUser::where([
                    'user_id' => $form->model()->user_id,
                    'family_id' => $form->model()->id,
                    'user_type' => 2,
                    'status' => 1,
                ])->delete();
            }
        });
        $form->saved(function (Form $form) {
            $checkFamilyUser = FamilyUser::where([
                'user_id' => $form->model()->user_id,
                'family_id' => $form->model()->id,
                'user_type' => 2,
                'status' => 1,
            ])->exists();
            if (!$checkFamilyUser) {
                User::where('id', $form->model()->user_id)->update(['family_id' => $form->model()->id]);
                FamilyUser::create([
                    'user_id' => $form->model()->user_id,
                    'family_id' => $form->model()->id,
                    'user_type' => 2,
                    'status' => 1,
                ]);
            }
        });

        return $form;
    }


    public function familySettings(Content $content)
    {

        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'family-setting');
        }
        $config = Config::whereIn('name', [
            'family_price',
        ])->pluck('value', 'name')->toArray();
        return $content->title(trans('Family Settings'))->view('familiesSettings', [
            'config' => $config,

        ]);
    }
}
