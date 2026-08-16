<?php

namespace App\Admin\Controllers;

use App\Models\TargetEdit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use PDF;
use App\Models\User;
use App\Models\Target;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Facades\Excel;
use App\Admin\Extensions\TargetsExport;
use App\Models\MonthlyDiamondReceive;
use Illuminate\Http\Request;


use Illuminate\Http\Request as HttpRequest;
use Encore\Admin\Controllers\HasResourceActions;

class TargetController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'target';

    public function index(Content $content)
    {
        checkAgencyFeature();

        return parent::index($content
            ->title(trans('targets'))
            ->body($this->grid()));
    }

    public function gridInstance()
    {
        return $this->grid();
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
            ->title(trans('targets'))
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
            ->title(trans('targets'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('targets'))
            ->body($this->form()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected bool $under_edit = false;


    protected function grid()
    {

        $this->under_edit = Target::where('under_edit', 1)->exists();

        $grid = new Grid(new Target);

        $grid->model()->orderBy('diamonds', 'asc');

        // $coins = Common::getMaxCoins();
        $convertDiamond = 'zones_coins';
        $coins = Common::getSettingValue($convertDiamond) ?? 1;

        $this->addLevelColumn($grid);
        $this->addDiamondsColumn($grid, $coins);
        $this->addUsdColumn($grid, $coins);
        $this->addAgencyShareColumn($grid, $coins);
        $this->addDbPercentageColumn($grid, $coins);
        // $this->addAppProfitColumn($grid, $coins);
        $this->addHoursDaysColumns($grid);
        $this->addReelColumn($grid);
        $this->addMomentColumn($grid);
        // $this->addConfirmColumn($grid);

        $this->addExportButton($grid);
        $this->addConfirmScript();

        $grid->tools(function (Grid\Tools $tools) {
            if ($this->under_edit) {
                $url = route('admin.targets.confirm');

                $tools->append('<button class="btn btn-sm btn-danger confirm-btn" data-url="' . $url . '">
                            <i class="fa fa-check"></i> ' . __('Confirm Update') . '
                        </button>');
            }
            return '';
        });

        Admin::html('
        <div id="loadingOverlay" 
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(255,255,255,0.7); z-index:999999; text-align:center;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);">
                <i class="fa fa-spinner fa-spin fa-3x"></i>
                <p style="margin-top:10px;">Loading...</p>
            </div>
        </div>
    ');


        $this->extendGrid($grid);
        $grid->disableExport();
        \Encore\Admin\Facades\Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        return $grid;
    }

    protected function addLevelColumn($grid)
    {
        $grid->column('level', __('target no'));
    }

    protected function addDiamondsColumn($grid, $coins)
    {
        $grid->diamonds(__('diamonds'))
            ->display(function ($value) use ($coins) {
                $old = $value;
                $new = $value;

                if ($this->under_edit && $this->edit) {
                    $new = $this->edit->data['diamonds'] ?? $value;
                }

                $endFormatted = $coins ? ($new / $coins) : 0;
                $endFormatted = common::roundToTwoDecimalPlaces($endFormatted);

                $display = $this->displayOldNewValue($old, $new, '💎 ');

                return "
                    <div style='display:flex;flex-direction:column;'>
                        {$display}
                        <span style='color:#888;font-size:smaller;'>\$ {$endFormatted}</span>
                    </div>
                ";
            });
    }

    protected function addUsdColumn($grid, $coins)
    {
        $grid->usd(__('Host Percentage'))
            ->display(function ($value) use ($coins) {
                $old = $value;
                $new = $value;

                if ($this->under_edit && $this->edit) {
                    $new = $this->edit->data['usd'] ?? $value;
                }

                $endFormatted = $this->diamonds / $coins;
                $userUsd = common::roundToTwoDecimalPlaces($endFormatted * $new / 100);

                $display = $this->displayOldNewValue($old, $new, '', true);

                return "
                    <div style='display:flex;flex-direction:column;'>
                        {$display}
                        <span style='color:#888;font-size:smaller;'>\$ {$userUsd}</span>
                    </div>
                ";
            });
    }

    protected function addAgencyShareColumn($grid, $coins)
    {
        $grid->agency_share(__('agency share'))
            ->display(function ($value) use ($coins) {
                $old = $value;
                $new = $value;

                if ($this->under_edit && $this->edit) {
                    $new = $this->edit->data['agency_share'] ?? $value;
                }

                $endFormatted = $this->diamonds / $coins;
                $userUsd = common::roundToTwoDecimalPlaces($endFormatted * $new / 100);

                $display = $this->displayOldNewValue($old, $new, '', true);

                return "
                    <div style='display:flex;flex-direction:column;'>
                        {$display}
                        <span style='color:#888;font-size:smaller;'>\$ {$userUsd}</span>
                    </div>
                ";
            });
    }

    protected function addDbPercentageColumn($grid, $coins)
    {
        $grid->db_percentage(__('DB Percentage'))
            ->display(function ($value) use ($coins) {
                $old = $value;
                $new = $value;

                if ($this->under_edit && $this->edit) {
                    $new = $this->edit->data['db_percentage'] ?? $value;
                }

                $endFormatted = $this->diamonds / $coins;
                $userUsd = common::roundToTwoDecimalPlaces($endFormatted * $new / 100);

                $display = $this->displayOldNewValue($old, $new, '', true);

                return "
                    <div style='display:flex;flex-direction:column;'>
                        {$display}
                        <span style='color:#888;font-size:smaller;'>\$ {$userUsd}</span>
                    </div>
                ";
            });
    }

    protected function addAppProfitColumn($grid, $coins)
    {
        $grid->app_profit_percentage(__('App Profit Percentage'))
            ->display(function ($value) use ($coins) {
                $old = $value;
                $new = $value;

                if ($this->under_edit && $this->edit) {
                    $new = $this->edit->data['app_profit_percentage'] ?? $value;
                }

                $endFormatted = $this->diamonds / $coins;
                $userUsd = common::roundToTwoDecimalPlaces($endFormatted * $new / 100);

                $display = $this->displayOldNewValue($old, $new, '', true);

                return "
                    <div style='display:flex;flex-direction:column;'>
                        {$display}
                        <span style='color:#888;font-size:smaller;'>\$ {$userUsd}</span>
                    </div>
                ";
            });
    }

    protected function addHoursDaysColumns($grid)
    {
        $grid->hours(__('hours'))->display(function ($value) {
            $old = $value;
            $new = $value;
            if ($this->under_edit && $this->edit) {
                $new = $this->edit->data['hours'] ?? $value;
            }
            return $this->displayOldNewValue($old, $new);
        });

        $grid->days(__('days'))->display(function ($value) {
            $old = $value;
            $new = $value;
            if ($this->under_edit && $this->edit) {
                $new = $this->edit->data['days'] ?? $value;
            }
            return $this->displayOldNewValue($old, $new);
        });
    }

    protected function addReelColumn($grid)
    {
        $grid->column('reel', __('Reel'))
            ->display(function ($value) {
                $old = explode(',', $value);
                $new = $old;

                if ($this->under_edit && $this->edit) {
                    $new = explode(',', $this->edit->data['reel'] ?? $value);
                }

                $labels = [__('admin.update'), __('admin.like'), __('admin.comment')];
                $html = '';
                foreach ($labels as $i => $label) {
                    $html .= "<div>{$label}: " . $this->displayOldNewValue($old[$i] ?? 0, $new[$i] ?? 0) . "</div>";
                }
                return $html;
            });
    }

    protected function addMomentColumn($grid)
    {
        $grid->column('moment', __('Moment'))
            ->display(function ($value) {
                $old = explode(',', $value);
                $new = $old;

                if ($this->under_edit && $this->edit) {
                    $new = explode(',', $this->edit->data['moment'] ?? $value);
                }

                $labels = [__('admin.update'), __('admin.like'), __('admin.comment')];
                $html = '';
                foreach ($labels as $i => $label) {
                    $html .= "<div>{$label}: " . $this->displayOldNewValue($old[$i] ?? 0, $new[$i] ?? 0) . "</div>";
                }
                return $html;
            });
    }


    protected function addConfirmColumn($grid)
    {
        $grid->column('confirm', __('Procedures'))
            ->display(function () {
                if ($this->under_edit) {
                    $url = route('admin.targets.confirm', $this->id);
                    return '<button class="btn btn-sm btn-success confirm-btn" data-url="' . $url . '">' . __('تأكيد التعديل') . '</button>';
                }
                return '';
            });
    }
    protected function displayOldNewValue($old, $new, $prefix = '', $isPercentage = false)
    {
        if ($old == $new) {
            $formatted = $isPercentage ? "% {$old}" : "{$prefix}{$old}";
            return "<span style='font-weight:bold;'>{$formatted}</span>";
        }

        $oldFormatted = $isPercentage ? "% {$old}" : "{$prefix}{$old}";
        $newFormatted = $isPercentage ? "% {$new}" : "{$prefix}{$new}";

        $color = $new > $old ? '#28a745' : '#dc3545';
        $arrow = $new > $old ? '↑' : '↓';

        return "
            <div style='display:flex;align-items:center;gap:5px;'>
                <span style='color:#dc3545;text-decoration:line-through;'>{$oldFormatted}</span>
                <span style='color:#6c757d;'>→</span>
                <strong style='color:{$color};'>{$newFormatted} {$arrow}</strong>
            </div>
        ";
    }



    // protected function addConfirmScript()
    // {

    //         Admin::script("
    //             document.querySelectorAll('.confirm-btn').forEach(function(button){
    //                 button.addEventListener('click', function(){
    //                     var url = this.dataset.url;

    //                     Swal.fire({
    //                         title: '".__('confirm_title')."',
    //                         html: '<p style=\"color: #000; font-weight: 500;\">".__('confirm_text')."</p>',
    //                         type: 'warning',
    //                         showCancelButton: true,
    //                         confirmButtonText: '".__('confirm_button')."',
    //                         cancelButtonText: '".__('cancel_button')."',
    //                         reverseButtons: true
    //                     }).then((result) => {
    //                         if (result.value) {
    //                             window.location.href = url;
    //                         }
    //                     });
    //                 });
    //             });
    //         ");



    // }

    protected function addConfirmScript()
    {
        Admin::script("
        document.querySelectorAll('.confirm-btn').forEach(function(button){
            button.addEventListener('click', function(){
                var url = this.dataset.url;

                Swal.fire({
                    title: '" . __('confirm_title') . "',
                    html: '<p style=\"color: #000; font-weight: 500;\">" . __('confirm_text') . "</p>',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '" . __('confirm_button') . "',
                    cancelButtonText: '" . __('cancel_button') . "',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {

                        $('#loadingOverlay').show();
                        $('.confirm-btn').prop('disabled', true);

                        window.location.href = url;
                    }
                });
            });
        });
    ");
    }





    protected function addExportButton($grid)
    {
        $grid->tools(function (Grid\Tools $tools) {
            $button = '<button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#exportPdfModal">
                        <i class="fa fa-download"></i> ' . __('export pdf') . '
                        </button>';
            $tools->append($button);
        });

        Admin::html(
            '<div class="modal fade" id="exportPdfModal" tabindex="-1" role="dialog" aria-labelledby="exportPdfLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                <form id="exportForm" method="GET" action="#" target="_blank">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h5 class="modal-title" id="exportPdfLabel">' . __('Choose Columns to Export') . '</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>
                        <div class="modal-body">
                        <label><input type="checkbox" name="columns[]" value="target_no" checked> ' . __('Target No') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="diamonds" checked> ' . __('Diamonds') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="usd" checked> ' . __('Host Percentage') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="agency_share" checked> ' . __('Agency Share') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="db_percentage" checked> ' . __('DB Percentage') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="hours" checked> ' . __('Hours') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="days" checked> ' . __('Days') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="reels" checked> ' . __('Reels') . '</label><br>
                        <label><input type="checkbox" name="columns[]" value="moments" checked> ' . __('Moments') . '</label><br>
                        </div>
                        <div class="modal-footer">
                        <button type="submit" formaction="/admin/download-target-pdf" class="btn btn-primary">' . __('Export PDF') . '</button>
                        <button type="submit" formaction="/admin/download-target-excel" class="btn btn-success">' . __('Export Excel') . '</button>
                    </div>
                    </div>
                    </form>
                </div>
                </div>'
        );
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Target::findOrFail($id));

        $show->id('ID');
        $show->level('target no');
        $show->diamonds('diamonds');
        //        $show->minuts('minuts');
        $show->hours('hours');
        $show->days('days');
        //        $show->img('img');
        //        $show->usd('usd');
        //        $show->coin('coin');
        //        $show->gold('gold');
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
        $form = new Form(new Target);
        $this->disableFormTools($form);

        //  $coins = Common::getMaxCoins();



        $form->display(__('ID'));

        $form->hidden('level', __('target no'))->default(function () {
            return Target::max('level') + 1;
        });




        $form->decimal('diamonds', __('diamonds'))
            ->help('
                <span id="total_usd_amount" style="font-weight:bold;color:green">' . __('Total USD: ') . '0.00 USD</span>')
            ->rules('min:0')
            ->default(0)
            ->required();

        $form->decimal('usd', __('Host Percentage') . '(%)')
            ->help('<span id="usd_amount">' . __('Amount will be: ')  . ' USD</span>')
            ->rules('min:0')
            ->default(0)
            ->required();

        $form->decimal('agency_share',  __('agency share') . '(%)')
            ->help('<span id="agency_amount">' . __('Amount will be: ')  . ' USD</span>')
            ->rules('min:0')
            ->default(0)
            ->required();

        $form->decimal('db_percentage',  __('DB  Percentage') . '(%)')
            ->help('<span id="super_admin_amount">' . __('Amount will be: ')  . ' USD</span>')
            ->rules('min:0')
            ->default(0)
            ->required();

        $form->decimal('app_profit_percentage', __('app profit Percentage') . '(%)')
            ->help('<span id="zone_amount">' . __('Amount will be: ')  . ' USD</span>')
            ->rules('min:0')
            ->default(100)
            ->disable();
        $convertDiamond = 'zones_coins';
        $coins = Common::getSettingValue($convertDiamond) ?? 1;
        $form->html('
        <script>
            $(document).ready(function () {
                var debounceTimer;
                var coins = ' . $coins . ';

                function floor2(num) {
                    return Math.floor(num * 100) / 100;
                }

                function calculateUsdAmount() {
                    var diamonds = parseFloat($("input[name=\'diamonds\']").val()) || 0;
                    var usd = parseFloat($("input[name=\'usd\']").val()) || 0;
                    var agency = parseFloat($("input[name=\'agency_share\']").val()) || 0;
                    var db = parseFloat($("input[name=\'db_percentage\']").val()) || 0;
                    var app = parseFloat($("input[name=\'app_profit_percentage\']").val()) || 0;

                    var totalUsd = diamonds / coins;
                    var userAmount = floor2(totalUsd * usd / 100);
                    var agencyAmount = floor2(totalUsd * agency / 100);
                    var zoneAmount = floor2(totalUsd * app / 100);
                    var superAdminAmount = floor2(totalUsd * db / 100);

                    $("#usd_amount").text("' . __('Amount will be: ') . '" + userAmount + " USD");
                    $("#agency_amount").text("' . __('Amount will be: ') . '" + agencyAmount + " USD");
                    $("#zone_amount").text("' . __('Amount will be: ') . '" + zoneAmount + " USD");
                    $("#super_admin_amount").text("' . __('Amount will be: ') . '" + superAdminAmount + " USD");
                    $("#total_usd_amount").text("' . __('Total USD: ') . '" + floor2(totalUsd) + " USD");
                }

                function enforceTotalPercentageLimit(changedField) {
                    var fields = ["usd", "agency_share", "db_percentage"];
                    var values = {};
                    var total = 0;

                    fields.forEach(function (field) {
                        values[field] = parseFloat($("input[name=\'" + field + "\']").val()) || 0;
                        total += values[field];
                    });

                    var remaining = floor2(100 - total);
                    if (remaining < 0) {
                        // لو المجموع أكبر من 100، نقص القيمة المدخلة نفسها
                        var currentValue = values[changedField];
                        var newValue = Math.max(0, currentValue + remaining);
                        $("input[name=\'" + changedField + "\']").val(floor2(newValue));
                        remaining = 0;
                    }

                    $("input[name=\'app_profit_percentage\']").val(remaining);
                }

                var allFields = ["diamonds", "usd", "agency_share", "app_profit_percentage", "db_percentage"];
                allFields.forEach(function (field) {
                    $(document).on("input", "input[name=\'" + field + "\']", function () {
                        var val = parseFloat($(this).val());

                        // منع القيم السالبة
                        if (val < 0) {
                            $(this).val(0);
                            val = 0;
                        }

                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(function () {
                            if (["usd", "agency_share", "db_percentage"].includes(field)) {
                                enforceTotalPercentageLimit(field);
                            }
                            calculateUsdAmount();
                        }, 500);
                    });
                });

                calculateUsdAmount();
            });

        </script>');
        $form->html('<h1>' . __('days and hours') . '</h1>');

        $form->number('hours', __('hours'))->default(function ($form) {
            $hours = $form->model()->hours;
            return $hours == null || $hours == '' ? 0 : $hours;
        });
        $form->number('days', __('days'))->default(function ($form) {
            $days = $form->model()->days;
            return $days == null || $days == '' ? 0 : $days;
        });


        $form->html('<h1>' . __('Reel') . '</h1>');

        $form->hidden('reel', 'reel');
        $form->number('reel1', __('uploadReel'))->default(function ($form) {
            $reel = $form->model()->reel;
            $str    = @explode(',', $reel)[0];
            return $str == null || $str == '' ? 0 : $str;
        });
        $form->number('reel2', __('LikeReel'))->default(function ($form) {
            $reel = $form->model()->reel;

            return @explode(',', $reel)[1] ?? 0;
        });;
        $form->number('reel3', __('commentReel'))->default(function ($form) {
            $reel = $form->model()->reel;

            return @explode(',', $reel)[2] ?? 0;
        });
        $form->html('<h1>' . __('Moment') . '</h1>');
        $form->hidden('moment', 'moment');

        $form->number('moment1', __('uploadMoment'))->default(function ($form) {
            $moment = $form->model()->moment;
            $str    = @explode(',', $moment)[0];
            return $str == null || $str == '' ? 0 : $str;
        });
        $form->number('moment2', __('likeMoment'))->default(function ($form) {
            $moment = $form->model()->moment;

            return @explode(',', $moment)[1] ?? 0;
        });
        $form->number('moment3', __('commentMoment'))->default(function ($form) {
            $moment = $form->model()->moment;

            return @explode(',', $moment)[2] ?? 0;
        });

        $form->editing(function (Form $form) {

            $target = Target::find($form->model()->id);

            if ($target) {
                $users = MonthlyDiamondReceive::where('monthly_diamond_received', '>=', $target->diamonds)->where('month', now()->month)->where('year', now()->year)->count();
                if ($users > 0) {
                    admin_warning('تحذير', __('target_change_warning'));
                }
            }

            $form->html(<<<HTML
                <script>
                    Dcat.ready(function () {
                        let hasWarning = $('div.alert-warning:contains("بعض المستخدمين")').length > 0;

                        if (hasWarning) {
                            $('form').on('submit', function (e) {
                                e.preventDefault();
                                Dcat.confirm('تحذير', 'بعض المستخدمين وصلوا إلى هذا الهدف. هل تريد حفظ التعديلات؟', function () {
                                    $('form').off('submit').submit(); // إعادة الإرسال بعد التأكيد
                                });
                            });
                        }
                    });
                </script>
                HTML);
        });




        $form->saving(function (Form $form) {


            $fields = [
                'usd' => request()->usd,
                'agency_share' => request()->agency_share,
                'app_profit_percentage' => request()->app_profit_percentage,
                'db_percentage' => request()->db_percentage,
            ];

            $total = 0;
            foreach ($fields as $key => $value) {
                if ($value < 0) {
                    $error = new MessageBag(
                        [
                            'title'   => 'forbidden',
                            'message' => __('The field :field must be a positive number.', ['field' => $key]),
                        ]
                    );
                    return back()->with(compact('error'));
                }

                $total += $value;
            }
            if ($total > 100) {
                $error = new MessageBag(
                    [
                        'title'   => 'forbidden',
                        'message' => __('The total percentage must be 100%.'),
                    ]
                );
                return back()->with(compact('error'));
            }



            if ($form->isEditing()) {
                $target = $form->model();

                $editData = [
                    'diamonds' => $form->diamonds,
                    'usd' => $form->usd,
                    'agency_share' => $form->agency_share,
                    'app_profit_percentage' => $form->app_profit_percentage,
                    'db_percentage' => $form->db_percentage,
                    'hours' => $form->hours,
                    'days' => $form->days,
                    'reel' => $form->reel1 . ',' . $form->reel2 . ',' . $form->reel3,
                    'moment' => $form->moment1 . ',' . $form->moment2 . ',' . $form->moment3,
                ];


                $edit = TargetEdit::updateOrCreate(
                    ['target_id' => $target->id],
                    [
                        'edited_by' => Auth::id(),
                        'data' => $editData,
                        'status' => 'pending',
                    ]
                );

                $target->under_edit = true;
                $target->edit_id = $edit->id;
                $target->save();
                $url = url('admin/targets');
                return redirect()->to($url);
            }
        });

        return $form;
    }




    public function update($id)
    {
        $data   = \request()->all();
        if (isset($data['reel1'])) {
            $reel1  = $data['reel1'];
            $reel2  = $data['reel2'];
            $reel3  = $data['reel3'];
            $values = [
                $reel1,
                $reel2,
                $reel3,
            ];

            $data = array_merge($data, ['reel' => implode(" ,", $values)]);
            unset($data['reel1']);
            unset($data['reel2']);
            unset($data['reel3']);
        }

        if (isset($data['moment1'])) {
            $moment1 = $data['moment1'];
            $moment2 = $data['moment2'];
            $moment3 = $data['moment3'];
            $values2 = [
                $moment1,
                $moment2,
                $moment3,
            ];

            $data = array_merge($data, ['moment' => implode(" ,", $values2)]);
            unset($data['moment1']);
            unset($data['moment2']);
            unset($data['moment3']);
        }



        \request()->replace($data);
        return $this->form()->update($id);
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        $data = \request()->all();
        $values = [
            $data['reel1'],
            $data['reel2'],
            $data['reel3'],
        ];

        $values2 = [
            $data['moment1'],
            $data['moment2'],
            $data['moment3'],
        ];

        $data = array_merge($data, ['reel' => implode(" ,", $values), 'moment' => implode(" ,", $values2)]);

        unset($data['reel1']);
        unset($data['reel2']);
        unset($data['reel3']);
        unset($data['moment1']);
        unset($data['moment2']);
        unset($data['moment3']);
        request()->replace($data);

        //        Target::create($data);
        return $this->form()->store();
    }


    public function downloadTargetPdf(HttpRequest $request)
    {
        try {
            $selectedColumns = $request->input('columns', []);

            $targets = Target::orderBy('diamonds')->get()->map(function ($target) {
                // Convert reel and moment string fields into arrays for view
                $target->reel_parts = array_map('trim', explode(',', $target->reel));
                $target->moment_parts = array_map('trim', explode(',', $target->moment));
                return $target;
            });
            //            $targets = Target::orderBy('diamonds')->get();
            //$pdf = Pdf::loadView('target_pdf', compact('targets'));
            //            $pdf = PDF::loadView('target_pdf', compact('targets'));
            //            return $pdf->download('target_data_' . now()->format('Y_m_d') . '.pdf');

            $pdf = PDF::loadView('target_pdf', [
                'targets' => $targets,
                'selectedColumns' => $selectedColumns,
            ]);

            return $pdf->download('target_data_' . now()->format('Y_m_d') . '.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function downloadTargetExcel(Request $request)
    {
        $selectedColumns = $request->input('columns', []);

        $targets = Target::orderBy('diamonds')->get()->map(function ($target) {
            $target->reel_parts = array_map('trim', explode(',', $target->reel));
            $target->moment_parts = array_map('trim', explode(',', $target->moment));
            return $target;
        });

        return Excel::download(new TargetsExport($targets, $selectedColumns), 'target_data_' . now()->format('Y_m_d') . '.xlsx');
    }

    public function confirm()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        Artisan::call('users:update-salaries');
        $targets = Target::where('under_edit', true)->get();

        if ($targets->isEmpty()) {
            admin_toastr(__('not found'), 'info');
            return back();
        }

        foreach ($targets as $target) {
            try {
                $this->applyPendingEdit($target);
                \App\Jobs\ProcessTargetDiamonds::dispatch($target)->onQueue('default');
            } catch (\Throwable $e) {
                //  \Log::error("فشل في تأكيد التارجيت رقم {$target->id}: " . $e->getMessage());
            }
        }
        admin_toastr(__('update_start'), 'info');
        return back();
    }

    protected function applyPendingEdit($target): void
    {


        if (!$target->under_edit || !$target->edit_id) {
            return;
        }

        $edit = TargetEdit::find($target->edit_id);
        if (!$edit) {
            return;
        }

        $data = $edit->data;

        $target->update([
            'diamonds' => $data['diamonds'] ?? $target->diamonds,
            'usd' => $data['usd'] ?? $target->usd,
            'agency_share' => $data['agency_share'] ?? $target->agency_share,
            'app_profit_percentage' => $data['app_profit_percentage'] ?? $target->app_profit_percentage,
            'db_percentage' => $data['db_percentage'] ?? $target->db_percentage,
            'hours' => $data['hours'] ?? $target->hours,
            'days' => $data['days'] ?? $target->days,
            'reel' => $data['reel'] ?? $target->reel,
            'moment' => $data['moment'] ?? $target->moment,
            'under_edit' => false,
            'edit_id' => 0,
        ]);

        $edit->delete();
    }
}
