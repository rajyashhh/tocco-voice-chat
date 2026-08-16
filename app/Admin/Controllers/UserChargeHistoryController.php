<?php

namespace App\Admin\Controllers;

use Carbon\Carbon;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;

class UserChargeHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'user charge history';

    public function indexCharge(Content $content, $user_id)
    {
        if (!request()->has('scope')) {
            return redirect()->to(url()->current() . '?scope=charge-to');
        }
        return $content
            ->title(trans('user charge history'))
            ->row(function ($row) use ($user_id) {
                $row->column(12, $this->gridTabs()); // <-- Tab buttons
            })
            ->row(function ($row) use ($user_id) {
                $row->column(12, $this->customGrid($user_id));
            });
    }
    protected function gridTabs()
    {
        $scope = request('scope', 'charge-to');

        $html = '
        <style>
            .tab-buttons {
                margin-bottom: 15px;
            }
            .tab-buttons .tab-button {
                color: black !important;
                margin-right: 10px;
                text-decoration: none;
                padding: 6px 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background-color: #f7f7f7;
            }
            .tab-buttons .tab-button.active {
                background-color: #007bff;
                color: white !important;
                border-color: #007bff;
            }
        </style>
        <div class="tab-buttons">
            <a href="?scope=charge-to" class="tab-button btn-dash ' . ($scope === 'charge-to' ? 'active' : '') . '">' . __('Charged to') . '</a>
            <a href="?scope=charge-from" class="tab-button btn-agency ' . ($scope === 'charge-from' ? 'active' : '') . '">' . __('Charged from') . '</a>
        </div>';

        return new \Encore\Admin\Widgets\Box(__(), $html);
    }


    protected function customGrid($userId)
    {
        $grid = new Grid(new Charge());
        $grid->disableRowSelector();

        $scope = request('scope');
        $model = $grid->model()->with(array_merge(Common::chargerRelationsQuery(), ['returnCharge']));


        if ($scope === 'charge-to') {
            $model->where('charger_id', $userId)
                ->where('charger_type', 'user');
        } else {
            $model->where('user_id', $userId)
                ->where('user_type', 'user');
        }

        $model->orderByDesc('created_at');

        $grid->column('id', __('ID'));

        if ($scope === 'charge-to') {
            $grid->column('admin.name', __('receiver'))->display(function () {
                return UserChargeHistoryController::renderUserInfo(Common::getReceiverInfo($this), $this->charger_type, $this->user_type);
            });
        } else {
            $grid->column('charger_id', __('charger'))->display(function () {
                return UserChargeHistoryController::renderUserInfo(Common::getChargerInfo($this), $this->charger_type, $this->user_type);
            });
        }

        $grid->column('amount', __('Coins'))->display(function () {
            return UserChargeHistoryController::renderCoinColumn($this->amount);
        });

        $grid->column('usd', __('USD'))->display(function () {
            return UserChargeHistoryController::renderUsdColumn($this->usd);
        });



        $grid->column('created_at', __('charge date'));
        if ($scope === 'charge-to') {
            $grid->column('return', __('return'))->display(function () {
                if ($this->returnCharge) {
                    return '<span class="label label-success">Returned</span>'; // ✅ show text
                } else {
                    return (new \App\Admin\Actions\ReturnChargeAction($this->id))->render();
                }
            });
        }

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="/admin/reset-salary" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>');
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();

        return $grid;
    }

    protected static function renderUserInfo($userInfo, $chargerType, $userType)
    {
        if (empty($userInfo['name']) && empty($userInfo['uuid'])) {
            return "<div style='display: flex; align-items: center; gap: 10px;'>
                        <span style='cursor: pointer;'>Unknown</span>
                    </div>";
        }

        $defaultImage = asset("images/businessman-icon.jpg");
        $url = getImagePath($userInfo['image']) ?? $defaultImage;
        if (!isImageExists($url)) {
            $url = $defaultImage;
        }

        $imageStyle = $chargerType === 'agency'
            ? 'width: 40px; height: 40px; object-fit: cover; border-radius: 0;'
            : 'width: 40px; height: 40px; object-fit: cover; border-radius: 50%;';

        $image = "<img src='{$url}' alt='User Image' style='{$imageStyle}'>";

        $translatedType = $userType === 'agency'
            ? __('Agency')
            : __('User');

        $nameWithType = "{$userInfo['name']} ";

        return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                    <a href='{$userInfo['url']}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        <span style=' text-decoration: underline; cursor: pointer;'>$nameWithType</span>
                        <p style='color: green; text-decoration: underline; cursor: pointer;'>({$translatedType})</p>
                    </a>
                    <div style='color: #aaa; font-size: smaller;'>UUID: {$userInfo['uuid']}</div>
                </div>
            </div>
        ";
    }


    public static function renderCoinColumn($coin): string
    {
        $coinIcon = asset('images/coin.jpg');
        $coin = number_format($coin);

        return "
            <div style='display: flex; align-items: center; gap: 5px;'>
                <span>{$coin}</span>
                <img src='{$coinIcon}' alt='Coin' width='20' height='20'>
            </div>
        ";
    }

    public static function renderUsdColumn($usd): string
    {
        $usdIcon = asset('images/dollar.jpg');
        $usd = number_format($usd, 2);

        return "
            <div style='display: flex; align-items: center; gap: 5px;'>
                <span>{$usd}</span>
                <img src='{$usdIcon}' alt='USD' width='20' height='20'>
            </div>
        ";
    }
}
