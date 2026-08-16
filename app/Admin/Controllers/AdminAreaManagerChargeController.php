<?php

namespace App\Admin\Controllers;


use App\Models\Charge;
use Encore\Admin\Grid;
use App\Helpers\Common;
use Encore\Admin\Auth\Permission;
use App\Models\ShippingAgency;
use Encore\Admin\Layout\Content;
use App\Enums\Charges\UserTypeEnum;
use App\Admin\Controllers\MainController;
use Modules\Country\Entities\SuperAdmin;
use Modules\Region\Entities\AreaManager;
use Modules\Region\Entities\SubAreaManager;


class AdminAreaManagerChargeController extends MainController
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
        Permission::check('browse-' . $this->permission_name);

        $content = $content
            ->header(trans('Charges'))
            ->description(trans('Charges'));
        $content->row(function ($row) {
            $row->column(12, $this->grid());
        });

        return $content;
    }
    protected function grid()
    {
        $grid = new Grid(new Charge());

        // Identity is derived from the authenticated manager, never from a raw
        // client-supplied session value. Only a super admin may preview another
        // manager via session('area_manager_id'); a real manager is pinned to
        // their own team root (own id for area managers, parent_id for subs).
        $authUser = auth()->user();
        $scopeRoot = $authUser->type == 'region' ? $authUser->id : $authUser->parent_id;
        $authId = Common::canPreviewAreaManager()
            ? (session('area_manager_id') ?? $scopeRoot)
            : $scopeRoot;
        $grid->model()
            ->with(['receiverUser', 'receiveragency', 'subAreaManager', 'areaManager'])
            ->where(function ($query) use ( $authId) {
                $query->where('charger_id', $authId)
                    ->orWhereIn('charger_id', SubAreaManager::where('parent_id', $authId)->pluck('id')->toArray());
            })
            ->whereIn('charger_type', [
                UserTypeEnum::AREA_MANAGER,
                UserTypeEnum::SUB_AREA_MANAGER,
            ])
            ->orderByDesc('id');

        $grid->filter(function (Grid\Filter $filter) use ($authId) {
            $filter->expand();

            $filter->where(function ($query) {
                if ($this->input) {
                    $query->where('user_type', 'agency')
                        ->where('user_id', $this->input);
                }
            }, __('Agency'))->select(ShippingAgency::pluck('name', 'id')->toArray());
            // if ($authUser->type == 'region') {
            //     $filter->where(function ($query) {
            //         if ($this->input) {
            //             $query->where('charger_id', $this->input);
            //         }
            //     }, __('created by'))->select(
            //         // Combine SubAreaManagers and AreaManager themselves
            //         SubAreaManager::where('parent_id', $authId)->pluck('name', 'id')
            //             ->merge(AreaManager::where('id', $authId)->pluck('name', 'id'))
            //             ->toArray()
            //     );
            // }


            $filter->where(function ($query) {
                if ($this->input) {
                    $query->where('user_type', UserTypeEnum::SUB_AREA_MANAGER)
                        ->where('user_id', $this->input);
                }
            }, __('Sub area manager'))->select(SubAreaManager::pluck('name', 'id')->toArray());

            $filter->where(function ($query) {
                if ($this->input) {
                    $query->where('user_type', UserTypeEnum::SUPER_ADMIN)
                        ->where('user_id', $this->input);
                }
            }, __('Super Admin'))->select(SuperAdmin::pluck('name', 'id')->toArray());
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
                    $profileUrl = url('areaManager/profile-shipping-agency/' . $info['uuid']);
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

            if ($info['type'] == 'sub_area_manager') {
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info['image']) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info['uuid'], $url, 40, 40);
                $showUrl = url("areaManager/area-manager-users/{$info['id']}");

                return "
                        <a href='#' style='text-decoration: none; color: inherit;'>
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

            if ($info['type'] == 'super_admin') {
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info['image']) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info['uuid'], $url, 40, 40);
                $showUrl = url("areaManager/superadmin-users-profile/{$info['id']}");

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
        });

        $grid->column('user_type', __('User Type'))->display(function ($value) {
            switch ($value) {
                case UserTypeEnum::AGENCY:
                    return "<span class='badge bg-primary'>" . __('Agency') . "</span>";
                case UserTypeEnum::SUB_AREA_MANAGER:
                    return "<span class='badge bg-success'>" . __('Sub area manager') . "</span>";
                case UserTypeEnum::SUPER_ADMIN:
                    return "<span class='badge bg-success'>" . __('Super Admin') . "</span>";
                default:
                    return "<span class='badge bg-secondary'>" . __('Unknown') . "</span>";
            }
        });

        $grid->column('created_at', __('created_at'))->display(function ($value) {
            return \Carbon\Carbon::parse($value)->translatedFormat('Y-m-d h:i A');
        });
       
            $grid->column('charger_id', __('created by'))->display(function () {
                $info = Common::getChargerInfo($this);



                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info['image']) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info['uuid'], $url, 40, 40);

                return "
                        <a href='#' style='text-decoration: none; color: inherit;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                {$image}
                                <div>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$info['name']}</span><br>
                                    <span style='color: #aaa; font-size: smaller;'>UUID: {$info['uuid']}</span>
                                </div>
                            </div>
                        </a>
                    ";


                return "<span class='text-danger'>" . __('لا يوجد مستلم') . "</span>";
            });
    


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
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . $coin . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        return $grid;
    }
}
