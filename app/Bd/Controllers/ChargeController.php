<?php

namespace App\Bd\Controllers;

use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Auth;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\AdminController;

class ChargeController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Charge';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */


    public function index(Content $content)
    {
        return $content
            ->header(trans('Charges'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
    }
    protected function grid()
    {
        $grid = new Grid(new Charge());



        $grid->model()->where('charger_type', 'bd')
            ->with('receiverUser', 'receiveragency')
            ->where('charger_id', Auth::user()->id)
            ->orderBy('id', 'desc');



        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->where(function ($query) {
                $uuid = $this->input;
                $query->where(function ($q) use ($uuid) {
                    $q->whereHas('receiverUser', function ($subQuery) use ($uuid) {
                        $subQuery->where('uuid', 'like', "%{$uuid}%");
                    })->orWhereHas('receiveragency', function ($subQuery) use ($uuid) {
                        $subQuery->where('id', 'like', "%{$uuid}%");
                    });
                });
            }, __('UUID'))->placeholder(__('ابحث في مستلم التحويل'));

                $filter->between('created_at', __('تاريخ الإنشاء'))->date();
       
        
        
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


        // $grid->column('id', __('Id'));
        // $grid->column('amount', __('Amount'));
        // $grid->column('amount_type', __('Amount type'));

        $grid->column('user_id', __('receiver'))->display(function () {
            $info = \App\Helpers\Common::getReceiverInfo($this);

            if ($info['type'] === 'agency') {
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $cacheKey = "agency_image_{$info['uuid']}";
                $image = \Cache::remember($cacheKey, 3600, function () use ($info) {
                    $path = $info['image'];
                    $defaultImage = asset("images/icon-agency.jpg");
                    $url = getImagePath($path) ?? $defaultImage;
                    if (!isImageExists($url)) $url = $defaultImage;
                    return handleShowImageWithTypes($info['uuid'], $url, 40, 40);
                });
                $profileUrl ='';
                if (!empty($info['uuid'])) {
                $profileUrl = route('bd.agency.profile', ['id' => $info['uuid']]);
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

            if ($info['type'] === 'user') {
                if (request()->filled('_export_')) {
                    return $info['name'];
                }
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info['image']) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info['uuid'], $url, 40, 40);
                $showUrl = url("bd/users/profile/{$info['id']}");

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


        $grid->column('created_at', __('تاريخ الإنشاء'))->display(function ($value) {
            return \Carbon\Carbon::parse($value)->translatedFormat('Y-m-d h:i A');
        });

        $grid->column('usd', __('usd'))->display(function ($value) {
            return number_format($value, 2);
        });
        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools) {
            $url = 'salaries';
            $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>';
            $tools->append($button);
        });
        $grid->disableRowSelector();
        $grid->actions(function (Grid\Displayers\Actions $actions) {
          
                $actions->disableDelete();
          
        });
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
        $charge = Charge::where('id', $id)
            ->where('charger_id', Auth::id())
            ->firstOrFail();

        $show = new Show($charge);

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
}
