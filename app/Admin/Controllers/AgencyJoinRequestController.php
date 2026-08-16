<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\Admin;
use App\Models\AgencyJoinRequest;
use App\Models\User;
use App\Models\UsersJoinedAgency;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class AgencyJoinRequestController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'agencies-join-requests';

    public function update($id)
    {

        if (request('_edit_inline') == "true") {
            if (request('status')) {
                request()->request->add(['change_status_admin_id' => Auth::id()]);
            }
        }
        return $this->form()->update($id);
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Join To Agency Requests'))
            ->body($this->grid()));
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
        return parent::show($id, $content
            ->title(trans('Join To Agency Requests'))
            ->body($this->detail($id)));
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
            ->title(trans('Join To Agency Requests'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Join To Agency Requests'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new AgencyJoinRequest);
        $countryID = Common::filterCountryIds();


        $grid->model()
            ->with([
                'user:id,name,uuid,phone,country_id,special_id,sender_level,received_level',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'user.profile:user_id,avatar',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'agency:id,name,img,country_id'
            ])
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->orderByDesc('id');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('uuid', 'like', '%' . $this->input . '%');
                });
            }, __('uuid'), 'text')->placeholder('ادخل UUID')->default('');

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', __('status'))->select([0 => __('pending'), 1 => __('accepted'), 2 => __('denied')]);
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency.id', __('agency id'));
            });
        });

        $grid->id(__('ID'));


        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        // App\Models\Admin is imported above and shadows the facade, so call
        // the facade explicitly (Admin::style on the model = BadMethodCall 500).
        \Encore\Admin\Facades\Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('agency.name', __('Agency'))
            ->display(function ($name) {
                $path = @$this->agency->img ?? '';
                $defaultImage = asset("images/icon-agency.jpg");
                $url = $path ? (getImagePath($path) ?? $defaultImage) : $defaultImage;
                $image = "<img src='" . e($url) . "' onerror=\"this.onerror=null;this.src='" . e($defaultImage) . "'\" style='height:40px; width:40px; border-radius:50%; object-fit:cover;' />";
                $showUrl = $this->agency ? url("admin/agencies/profile/{$this->agency->id}") : '#';

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <span>$name</span>
                </div>
            ";
            });
        $grid->column('user.phone', __('whatsapp'))->display(function ($number) {
            if (!$number) return '-';

            $iconUrl = asset('images/whatsapp.png'); // Adjust the path based on your actual file location

            // Return an image with a WhatsApp link
            return "<div style='display: flex; align-items: center; '>

                <span>{$number} </span>

                  <img src='{$iconUrl}' alt='USD' width='20' height='20' style='margin-left:3px; filter: invert(1);'>
            </div>";
        });
        $grid->column('status', __('status'))->display(function ($status) {
            $statuses = [
                0 => ['label' => __('pending'), 'color' => 'orange'],
                1 => ['label' => __('accepted'), 'color' => 'green'],
                2 => ['label' => __('denied'), 'color' => 'red'],
            ];

            $badgeColor = $statuses[$status]['color'] ?? 'orange';
            $statusLabel = $statuses[$status]['label'] ?? __('pending');

            return "<span style='display: inline-block; padding: 5px 10px; color: white; background-color: $badgeColor; border-radius: 5px;'>
                        $statusLabel
                    </span>";
        });
        $grid->column('change_status_admin_id', __('Change Status Admin'))
            ->display(function () {
                $adminId = $this->change_status_admin_id;
                if (!$adminId) return '-';

                $admin = Admin::find($adminId) ?? User::find($adminId);
                if (!$admin) return '-';

                $name = $admin->name ?? 'Unknown';
                $uid = $admin->uuid ?? 'N/A';
                $path = @$admin->profile?->avatar ?? @$admin->avatar ?? '';
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = $path ? (getImagePath($path) ?? $defaultImage) : $defaultImage;
                $image = "<img src='" . e($url) . "' onerror=\"this.onerror=null;this.src='" . e($defaultImage) . "'\" style='height:40px; width:40px; border-radius:50%; object-fit:cover;' />";
                $type = userType(@$admin?->type_user ?? '') ?? 'Unknown Type';

                return "
        <div style='display: flex; align-items: center; gap: 10px;'>
            $image
            <div>
                <strong>$name</strong><br>
                <span style='color: #aaa; font-size: smaller;'>$type</span>
            </div>
        </div>
        ";
            });

        $grid->column('created_at', trans('time'))->diffForHumans();
        // $grid->column('created_at', __('Created at'))->display(function ($date) {
        //     return Carbon::parse($date)->format('Y-m-d H:i:s');
        // });
        $this->extendGrid($grid);


        $grid->disableCreateButton();
        $grid->disableExport();
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AgencyJoinRequest::findOrFail($id));

        //        $show->id('ID');
        //        $show->user_id('user_id');
        //        $show->agency_id('agency_id');
        //        $show->status('status');
        //        $show->change_status_admin_id('change_status_admin_id');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));

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

        $form = new Form(new AgencyJoinRequest);
        $this->disableFormTools($form);

        $form->display(__('ID'));
        $form->text('user_id', __('user id'));
        $form->text('agency_id', __('agency id'));
        $form->select('status', __('status'))->options(
            [
                0 => __('pending'),
                1 => __('accepted'),
                2 => __('denied')
            ]
        );;
        $form->hidden('change_status_admin_id', 'change_status_admin_id');
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));
        $form->saving(function (Form $form) {


            if ($form->model()->status == 1) {
                $user = User::query()->where('id', $form->model()->user_id)->first();
                if (($user->agency_id)) {
                    $error = new MessageBag(
                        [
                            'title'   => 'forbidden',
                            'message' => 'user already in agency',
                        ]
                    );
                    return back()->with(compact('error'));
                }
                // UserCommon::userVip($user,'agency-join-dash');

                $user_id = $form->model()->user_id;
                $checkAgencyUser = UsersJoinedAgency::where([
                    'user_id' => $user_id,
                    'agency_id' => $form->model()->agency_id,
                    'type' => 2,
                ])->where('leave_date', null)->exists();
                if (!$checkAgencyUser) {
                    UsersJoinedAgency::create([
                        'user_id' => $user_id,
                        'agency_id' => $form->model()->agency_id,
                        'type' => 2,
                        'join_date' => now(),
                        'status' => 'Joined'
                    ]);
                }

                $update = DB::table('users')
                    ->where('id', $user_id)
                    ->update(['type_user' => 1]);


                if (!$update) {
                    $error = new MessageBag([
                        'title' => 'Error',
                        'message' => 'Failed to update user',
                    ]);
                }

                uploadMonthlyDiamondReceive($user_id, 0);
            }
        });


        return $form;
    }
}
