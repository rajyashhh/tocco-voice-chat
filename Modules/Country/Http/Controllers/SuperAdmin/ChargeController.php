<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Modules\Country\Entities\SubAdmin;
use Modules\Country\Entities\SuperAdmin;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use App\Enums\PermissionType;
use App\Models\ShippingAgency;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;
use App\Enums\Charges\UserTypeEnum;
use Illuminate\Support\Facades\Auth;
use App\Admin\Controllers\MainController;

class ChargeController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Charge';
    public $permission_name = 'coin-recharge';

    /**
     * Make a grid builder.
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        $user = Auth::user();
        $totals = Charge::selectRaw("
            SUM(CASE WHEN user_type = ? AND user_id = ? THEN amount ELSE 0 END) as total_charges,
            SUM(CASE WHEN charger_type = ? AND charger_id = ? THEN amount ELSE 0 END) as total_spent
        ", [
            UserTypeEnum::SUPER_ADMIN,
            $user->id,
            UserTypeEnum::SUPER_ADMIN,
            $user->id
        ])
            ->first();

        $totalCharges = $totals->total_charges;
        $totalSpent   = $totals->total_spent;

        $finalSalary = $user->di;
        return parent::index($content
            ->header(trans('Charges'))
            ->description(trans('Charges'))

            ->row(function ($row) use ($finalSalary) {
                $row->column(12, view('admin.grid.superadmin.wallet', ['finalSalary' => $finalSalary]));
            })
            ->row(function (Row $row) use ($totalCharges, $totalSpent) {
                $row->column(6, new InfoBox(__('total charges'), 'money', 'green', '', truncateAndTrim($totalCharges, 2) . ' 💰'));
                $row->column(6, new InfoBox(__('total spent'), 'money', 'red', 'charges', truncateAndTrim($totalSpent, 2)));
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }
    protected function grid()
    {
        $grid = new Grid(new Charge());
        $auth = auth()->user();
        $ids = auth()->user()->type == 'country' ? SuperAdmin::with('subSuperAdmins:id,parent_id')
            ->find($auth->id)
            ->subSuperAdmins
            ->pluck('id')
            ->prepend($auth->id) // add superadmin id to the beginning
            ->toArray() : SubAdmin::where('parent_id', $auth->parent_id)
            ->pluck('id')
            ->prepend($auth->parent_id) // add superadmin id
            ->toArray();
        $grid->model()
            ->where(function ($query) {
                $query->where('charger_type', UserTypeEnum::SUPER_ADMIN)
                    ->orWhere('charger_type', UserTypeEnum::SUB_ADMIN);
            })
            ->with('receiverSubAdmin', 'receiveragency')
            ->whereIn('charger_id', $ids)
            ->orderBy('id', 'desc');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->where(function ($query) {
                if ($this->input) {
                    $query->where('user_type', 'agency')
                        ->where('user_id', $this->input);
                }
            }, __('Agency'))->select(ShippingAgency::pluck('name', 'id')->toArray());

            $filter->where(function ($query) {
                if ($this->input) {
                    $query->where('user_type', UserTypeEnum::SUB_ADMIN)
                        ->where('user_id', $this->input);
                }
            }, __('Sub Super Admin'))->select(SubAdmin::pluck('name', 'id')->toArray());
        });

        $grid->column('user_id', __('receiver'))->display(function () {
            $info = Common::getReceiverInfo($this);

            if ($info['type'] == 'agency') {
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $cacheKey = "agency_image_{$info['uuid']}";
                $image = \Cache::remember($cacheKey, 3600, function () use ($info) {
                    $path = $info['image'];
                    $defaultImage = asset("images/icon-agency.jpg");
                    $url = getImagePath($path) ?? $defaultImage;
                    if (!isImageExists($url)) $url = $defaultImage;
                    return handleShowImageWithTypes($info['uuid'], $url, 40, 40, 0);
                });
                $profileUrl = '';
                if (!empty($info['uuid'])) {
                    // dd($info['uuid']);
                    $profileUrl = url('superadmin/shipping-agencies/profile/' . $info['uuid']);
                }
                return "
                        <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                {$image}
                                <div>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$info['name']}</span><br>
                                    <span style='font-size: smaller;'>ID: {$info['uuid']}</span>
                                </div>
                            </div>
                        </a>
                    ";
            }

            if ($info['type'] == 'sub_super_admin') {
                $showUrl = '#';
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info['image']) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info['uuid'], $url, 40, 40);
                if (!empty($info['uuid'])) {
                    $showUrl = url("superadmin/auth-users/{$info['id']}");
                }

                return "
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                {$image}
                                <div>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$info['name']}</span><br>
                                    <span style='color: #aaa; font-size: smaller;'>UUID: {$info['uuid']}</span>
                                </div>
                            </div>
                        </a>
                    ";
            }

            return "<span class='text-danger'>" . __('لا يوجد مستلم') . "</span>";
        })->sortable();

        $grid->column('user_type', __('User Type'))->display(function ($value) {
            switch ($value) {
                case UserTypeEnum::AGENCY:
                    return "<span class='badge bg-primary'>" . __('Agency') . "</span>";
                case UserTypeEnum::SUB_ADMIN:
                    return "<span class='badge bg-success'>" . __('Sub Super Admin') . "</span>";
                default:
                    return "<span class='badge bg-secondary'>" . __('Unknown') . "</span>";
            }
        });

        $grid->column('created_at', __('created_at'))->display(function ($value) {
            return \Carbon\Carbon::parse($value)->translatedFormat('Y-m-d h:i A');
        })->sortable();

        $grid->column('amount', __('Amount'))->display(function ($coin) {
            $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
            if (request()->filled('_export_')) {
                return $coin ?? 0;
            }
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . truncateAndTrim($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });

        $grid->column('usd', __('usd'))->display(function ($coin) {
            $icon = asset('images/dollar.jpg'); // تأكد من وجود الصورة في هذا المسار
            //            number_format();
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . $coin . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        })->sortable();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
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
        $show = new Show(Charge::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('charger_id', __('Charger id'));
        $show->field('charger_type', __('Charger type'));
        $show->field('user_charger_type', __('User charger type'));
        $show->field('user_id', __('User id'));
        $show->field('user_type', __('User type'));
        $show->field('amount', __('Amount'));
        $show->field('amount_type', __('Amount type'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('balance_before', __('Balance before'));
        $show->field('is_used_transferred', __('Is used transferred'));
        $show->field('usd', __('Usd'));
        $show->field('agency_id', __('Agency id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Charge());

        // $form->number('charger_id', __('Charger id'));
        $form->text('charger_type', __('Charger type'));
        $form->text('user_charger_type', __('User charger type'));
        $form->number('user_id', __('user id'));
        // $form->text('user_type', __('User type'));
        // $form->decimal('amount', __('Amount'))->default(0.00);
        $form->switch('amount_type', __('Amount type'))->default(1);
        // $form->decimal('balance_before', __('Balance before'));
        $form->switch('is_used_transferred', __('Is used transferred'));
        $form->decimal('usd', __('usd'));
        $form->number('agency_id', __('Agency id'));

        return $form;
    }


    public function getSubAdmins(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $query = DB::table('admin_users')
            ->where('type', PermissionType::SUB_SUPER_ADMIN->value)
            ->where('is_preview', 0)
            ->where('parent_id', auth()->id());


        if ($key) {
            $query->where(function ($q) use ($key) {
                $q->where('name', 'like', "%{$key}%")
                    ->orWhere('username', 'like', "%{$key}%")
                    ->orWhere('id', $key);
            });
        }

        $total = $query->count();

        $users = $query->select('id', 'name', 'username')
            ->paginate($perPage, ['*'], 'page', $page);


        return response()->json([$users]);
    }
}
