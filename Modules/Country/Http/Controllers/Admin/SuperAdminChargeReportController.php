<?php

namespace Modules\Country\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Row;
use App\Models\ChargeInvoice;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;

use App\Enums\Charges\UserTypeEnum;
use App\Admin\Controllers\MainController;
use Modules\Country\Entities\SuperAdmin;
use Modules\Country\Actions\Admin\SuperAdminChargeAction;
use Modules\Country\Actions\Admin\ChargeSuperAdminHistoryAction;

class SuperAdminChargeReportController extends MainController
{
    public $permission_name = 'charger-reports';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans("Reports"))
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $box = new Box();
                    $box->content($this->combinedContent());
                    $column->append($box);
                });
            }));
    }

    private function combinedContent()
    {
        $tabs = $this->tabsComponent();
        $grid = $this->grid()->render();

        return "<div style='margin-bottom: 20px;'>{$tabs}</div>{$grid}";
    }

    protected function grid()
    {
        $name = "result";
        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableColumnSelector();

        return $grid;
    }

    public function form()
    {
        $form = new Form(new Charge);
        return $form;
    }

    protected function result()
    {
        $charger_type = "dash";
        if (request("name") == "app") {
            $charger_type = "app";
        }

        $grid = new Grid(new Charge());

        $grid->filter(function (Grid\Filter $filter) {

            $filter->expand();

            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('created_at', '>=', $date);
                }, __('from_date'), 'from_date')->date();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = UserCommon::arabicToEnglishNumbers($this->input);

                    $query->whereDate('created_at', '<=', $date);
                }, __('to_date'), 'to_date')->date();
            });
            $filter->column(1 / 2, function ($filter) {

                // Custom filter for increase/decrease/no change
                $filter->where(function ($query) {
                    $value = request('changes_type'); // get the selected value

                    if ($value === 'increase') {
                        $query->where('amount', '>', 0);
                    } elseif ($value === 'decrease') {
                        $query->where('amount', '<', 0);
                    }
                }, __('Charge Type'),'changes_type')->select([
                    'increase'  => __('increase'),
                    'decrease'  => __('decrease'),
                ]);
            });

             $filter->column(1 / 2, function ($filter) {

                // Custom filter for increase/decrease/no change
                $filter->equal('charger_id', __('created by'));
            });
        });



        Admin::script(
            <<<JS
            $(document).ready(function() {
                $('.form-control[id$="_date"]').datetimepicker({
                    format: 'YYYY-MM-DD'
                });
            });

            $(document).on('pjax:complete', function() {
                $('.form-control[id$="_date"]').datetimepicker({
                    format: 'YYYY-MM-DD'
                });
            });

            var observer = new MutationObserver(function(mutations) {
                $('.form-control[id$="_date"]').datetimepicker({
                    format: 'YYYY-MM-DD'
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        JS
        );

        Admin::script('
        $(document).on("click", ".submit", function () {
            setTimeout(function() {
                location.reload();
            }, 500);
        });
    ');

        $grid->model()
         ->when(request('changes_type') =='increase', fn($q) => $q->where('amount', '>', 0))
          ->when(request('changes_type') =='decrease', fn($q) => $q->where('amount', '<', 0))
            ->where('user_id', '=', request('id'))
            ->orderByDesc('created_at')->with(['sender', 'receiver']);

        if ($charger_type == "dash") {
            $grid->model()->where('charger_type', "dash")
                ->orWhere('charger_type', UserTypeEnum::AREA_MANAGER);
        } else {
            $grid->model()->where('charger_type', "!=", "dash")
                ->orWhere('charger_type', '!=', UserTypeEnum::AREA_MANAGER);
        }


        $grid->column('id', __('transaction id'));
        $grid->column('charger_id', __("created by"))->display(function () use ($charger_type) {

            $sender = Common::getChargerInfo($this);
            $name = $sender['name'];
            $uuid = $sender['uuid'];
            $path = $sender['image'];
            $showUrl = $sender['url'];

            $defaultImage = asset("images/businessman-icon.jpg");
            $url = $path ?? $defaultImage;
              // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            return "
                 <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>

                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uuid</span>
                    </div>
                </div>
             </a>

            ";
        });
        $grid->column('amount_and_usd', __('coins & USD'))->display(function () {
            $coin = number_format($this->amount); // Assuming 'amount' is the coin value
            $usd = $this->usd;

            $coinIcon = asset('images/coin.jpg');
            $usdIcon = asset('images/dollar.jpg');

            return "
                    <div style='display: flex; flex-direction: column; gap: 5px;'>
                        <div style='display: flex; align-items: center; gap: 5px;'>
                            <span>{$coin}</span>
                            <img src='{$coinIcon}' alt='Coin' width='20' height='20'>
                        </div>
                        <div style='display: flex; align-items: center; gap: 5px;'>
                            <span>{$usd}</span>
                            <img src='{$usdIcon}' alt='USD' width='20' height='20'>
                        </div>
                    </div>
                ";
        });
        $grid->column('change_type', __('change_type'))
            ->display(function () {
                if ($this->amount > 0) {
                    return "<span style='color:green; font-weight:bold;'>" . __('increase') . "</span>";
                } elseif ($this->amount < 0) {
                    return "<span style='color:red; font-weight:bold;'>" . __('decrease') . "</span>";
                } else {
                    return "<span style='color:gray;'>" . __('no_change') . "</span>";
                }
            });
        $grid->column('balance_before', __("amount before"))->display(function ($coin) {
            $balance_after = $this->balance_before;
            $icon = asset('images/coin.png'); // أيقونة نزول إذا كان الرصيد بعد أقل من قبل

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                   <span>
                   " . number_format($balance_after) . "</span>
                   <img src='{$icon}' alt='USD' width='20' height='20'>
                   </div>";
        });

        $grid->column('balance_after', __("amount after"))->display(function () {

            $balance_after = $this->amount + $this->balance_before;
            $icon = asset('images/arrows.png'); // أيقونة صعود أو نزول حسب المبلغ

            return "<div style='display: flex; align-items: center; gap: 5px;'>
            <span>
            " . number_format($balance_after) . "</span>
            <img src='{$icon}' alt='USD' width='20' height='20'>
            </div>";
        });

        $grid->column('custom_button2', __('reason'))->modal(__('reason'), function ($model) {
            $reason = ChargeInvoice::where('charge_id', $this->id)->first();

            if (!$reason) {
                return "<table class='table'><tr><td>" . __('No reasons available') . "</td><td>-</td></tr></table>";
            }

            Admin::style(
                <<<CSS
                .modal-reason-table td {
                    max-width: 300px;
                    word-wrap: break-word;
                    white-space: pre-wrap;
                    padding: 10px;
                }
                CSS
            );

            $img = getDriverUrl() . '/' . $reason->invoice;
            $imgHtml = "<img src='" . $img . "' style='width:50px;height:50px' class='img img-thumbnail' />";

            $html = "<table class='table modal-reason-table'>";
            $html .= "<tr><td>" . __('Reason') . "</td><td>" . htmlspecialchars(
                app()->getLocale() === 'en'
                    ? ($reason->reason_en ?? $reason->reason_ar)
                    : ($reason->reason_ar ?? $reason->reason_en)
            ) . "</td></tr>";
            $html .= "<tr><td>" . __('Invoice') . "</td><td>" . $imgHtml . "</td></tr>";
            $html .= "</table>";

            return $html;
        });

        $grid->column('created_at', __('shipping date'));



        $grid->disableRowSelector();
        if (Admin::user()->can('add-switch-' . $this->permission_name) || Admin::user()->can('*') || Admin::user()->can('history-switch-' . $this->permission_name)) {

            $grid->tools(function (Grid\Tools $tools) {
                $idFromRoute = request()->route('id');

                $tools->append(

                    (new ChargeSuperAdminHistoryAction())
                        ->setUserId($idFromRoute ?? null) // $this->id might not work in tools, see note below
                        ->render()
                );
            });
        }
        return $grid;
    }

    private function tabsComponent()
    {
        $superAdmin = SuperAdmin::find(request('id'));

        return view('admin.grid.common.report.superAdminCharges', compact('superAdmin'))->render();
    }

    public function showChargeReports(Content $content, $agency_id)
    {
        if (!request()->has('scope')) {
            return redirect()->to(url()->current() . '?scope=dash');
        }

        return $content
            ->title(__('Charge Reports'))
            ->body($this->customGrid($agency_id));
    }

    protected function customGrid($agency_id)
    {
        $grid = new Grid(new Charge());
        $grid->model()->where('agency_id', $agency_id);

        // Add tabs to the header
        $grid->header(function () {
            $scope = request('scope', 'dash');
            return '
        <div class="tab-buttons">
            <a href="?scope=dash" class="tab-button btn-dash ' . ($scope === 'dash' ? 'active' : '') . '">' . __('Charged by dash') . '</a>
            <a href="?scope=not_dash" class="tab-button btn-agency ' . ($scope === 'not_dash' ? 'active' : '') . '">' . __('Charged by app') . '</a>
        </div>
    ';
        });

        // Apply scope based on query parameter
        $scope = request('scope');
        if ($scope === 'dash') {
            $grid->model()->where('charger_type', 'dash');
        } else {
            $grid->model()->where('charger_type', '!=', 'dash');
        }

        // Add filters
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->where(function ($query) {
                $date = UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '>=', $date);
            }, __('from_date'), 'from_date')->date();

            $filter->where(function ($query) {
                $date = UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '<=', $date);
            }, __('to_date'), 'to_date')->date();
        });

        // Define columns
        $grid->column('id', __('ID'));
        if ($scope === 'dash') {
            $grid->column('admin.name', __('creator'))->display(function () {
                $name = $this->admin->name ?? '';
                $path = $this->admin->avatar ?? null;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                $showUrl = '#';
                if ($this->admin && $this->admin->id) {
                    $showUrl = url("admin/auth/users/{$this->admin->id}");
                }

                return "
                 <div style='display: flex; align-items: center; gap: 10px;'>
                     <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         $image
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                     </a>
                 </div>
                ";
            });
        }

        if ($scope !== 'dash') {
            $grid->column('charger_id', __('charger'))->display(function () {
                if ($this->charger_type == 'agency') {
                    $name = $this->agency->name ?? 'No Agency';
                    $path = $this->agency->img ?? null;
                    $defaultImage = asset("images/icon-agency.jpg");
                    $url = getImagePath($path) ?? $defaultImage;

                    if (!isImageExists($url)) {
                        $url = $defaultImage;
                    }
                    $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                    $showUrl = $this->agency ? url("admin/agencies/{$this->agency->id}") : '#';
                    $link = $this->agency ? "
                                                <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                                    <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                                                </a>
                                            " : "<span style='color: gray;'>No Agency</span>";

                    return "
                                <div style='display: flex; align-items: center; gap: 10px;'>
                                    $image
                                    $link
                                </div>
                            ";
                } else {
                    $name = $this->user->name ?? 'No User';
                    $uid = $this->user->uuid ?? 'N/A';
                    $path = $this->user->profile->avatar ?? null;
                    $defaultImage = asset("images/businessman-icon.jpg");
                    $url = getImagePath($path) ?? $defaultImage;

                    if (!isImageExists($url)) {
                        $url = $defaultImage;
                    }
                    $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                    return "
                                <div style='display: flex; align-items: center; gap: 10px;'>
                                    $image
                                    <div>
                                        <strong>$name</strong><br>
                                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                                    </div>
                                </div>
                            ";
                }
            });
        }

        $grid->column('amount', __('Amount'));
        $grid->column('amount', __('Amount'));
        if ($scope === 'not_dash') {
            // dd(123);
            $grid->column('amount_type', __('status'))->display(function () use ($agency_id) {
                return $this->user_id == $agency_id  ?  __('increment') : __('decrement');
            });
        } else {
            $grid->column('amount_type', __('status'))->display(function () {
                return $this->amount < 0 ? __('decrement') : __('increment');
            });
        }
        $grid->column('created_at', __('Created at'));

        // Disable unnecessary buttons
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableRowSelector();


        return $grid;
    }
}
