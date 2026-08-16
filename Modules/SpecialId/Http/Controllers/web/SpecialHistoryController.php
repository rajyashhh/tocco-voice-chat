<?php

namespace Modules\SpecialId\Http\Controllers\web;

use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use Encore\Admin\Controllers\AdminController;
use Modules\SpecialId\Entities\SpecialHistory;
use Encore\Admin\Facades\Admin;

class SpecialHistoryController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'details-of-unique-identifiers';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('special-histories'))
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
        return $content
            ->title(trans('special-histories'))
            ->body($this->detail($id));
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
        return $content
            ->title(trans('special-histories'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('special-histories'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SpecialHistory());
        $countryID = Common::filterCountryIds();

        $grid->model()->with(['user','user.profile', 'ware','user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')])->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)));

        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('ware.value', __('UUID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($from = request('from_date')) {
                        $start = Carbon::parse(convertArabicToEnglishNumbers($from))->startOfDay();
                        $query->whereDate('created_at',  $start);
                    }
                }, __('start date'), 'from_date')->date();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {}, __('end date'), 'end_date')->date();
            });
        });
        if ($end = request('end_date')) {
            $endDate = \Carbon\Carbon::parse(convertArabicToEnglishNumbers($end))->toDateString();

            $grid->model()->whereHas('ware', function ($q) use ($endDate) {
                $q->whereRaw(
                    "DATE(DATE_ADD(special_id_histories.created_at, INTERVAL wares.expire DAY)) = ?",
                    [$endDate]
                );
            });
        }
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('User'))->display(function () {
            $name = @$this->user->name ?? '';
            $uid = @$this->user->uuid ?? 0;
            if ($this->user && request()->filled('_export_')) {

                return "{$name} (UUID: {$uid})";
            }
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($this->user?->profile?->avatar) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            return '
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="' . $url . '" alt="User Image" style="width: 40px; height: 40px;">
                    <div>
                        <a href="/admin/users/' . $this->user_id . '" style="text-decoration: none; font-weight: bold;">' . $name . '</a>
                        <div style="font-size: 12px;">' . 'Uuid: ' . $this->user?->uuid . '</div>
                    </div>
                </div>
            ';
        });
        if (!request()->filled('_export_')) {
            $grid->column('ware.value', __('value'))->display(function ($coin) {
                $icon = asset('images/coin.png'); // Ensure this path is correct
                return '<img src="' . $icon . '" alt="coin" style="width: 20px; height: 20px; margin-right: 5px;">' . $coin ?? 0;
            });
            $grid->column('ware.show_img', __('image'))->display(function ($path) {
                if ($this->ware) {
                    $url = getImagePath($path);
                    return handleShowImageWithTypes($this->id, $url, 50, 50);
                }
                /** @var Ware $this */
            });
            $grid->column('ware.get_type', __('get_type'))
                ->display(function ($value) {
                    if (is_null($value)) {
                        return "<span style='color:red;'>⚠️ نوع غير متوفر</span>";
                    }
                    return $value;
                })
                ->select([
                    4 => trans('purchase'),
                    6 => trans('limited time purchase'),
                ]);
        } else {
            $grid->model()->whereHas('ware');
            $grid->column('ware.name', __('value'))->display(function ($vale) {
                if (@$this->ware && request()->filled('_export_')) {
                    $name = $vale ?? '';
                    $id = $this->ware->id ?? 0;
                    return "{$name} (ID: {$id})";
                } else {
                    return '-';
                }
            });

            $grid->column('ware.get_type', __('get_type'))->display(function ($status) {

                if (@$this->ware->get_type && request()->filled('_export_')) {
                    return $status == 4 ? trans('purchase') : trans('limited time purchase');
                } else {
                    return '';
                }
                // استخدم الشهر والسنة كمعاملات إذا لزم الأمر

            });
        }
        $grid->column('status', __('status'))->display(function ($status) {
            if (request()->filled('_export_')) {
                return $status == 1 ? __('active') : __('inactive');
            }
            // استخدم الشهر والسنة كمعاملات إذا لزم الأمر
            return $status == 1 ? "<span class='label-success' " . 'style="width: 8px;height: 8px;padding: 0;border-radius: 50%;display: inline-block;"' .
                "></span>" : "<span class='label-warning' " . 'style="width: 8px;height: 8px;padding: 0;border-radius: 50%;display: inline-block;"' .
                "></span>";
        });

        $grid->column('created_at', __('start date'))
            ->display(function ($value) {
                $timezone = getTimezone();
                return Carbon::parse($value)->setTimezone($timezone)->format('Y-m-d');
            });

        $grid->column('ware.expire', __('end date'))
            ->display(function ($value) {
                $timezone = getTimezone();
                if (!@$this->ware->get_type) {
                    return '-';
                }
                if ($this->ware->get_type == 4) {
                    return '∞';
                }



                return Carbon::parse($this->created_at)
                    ->addDays($this->ware->expire) // Add expire days
                    ->setTimezone($timezone)
                    ->format('Y-m-d');
            });
        //        $grid->column('created_at', trans('admin.created_at'))->diffForHumans();
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $this->extendGrid($grid);

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
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
        $show = new Show(SpecialHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('status', __('Status'));
        $show->field('user_id', __('User id'));
        $show->field('ware_id', __('Ware id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SpecialHistory());
        $this->disableFormTools($form);

        $form->switch('status', __('Status'));
        $form->number('user_id', __('User id'));
        $form->number('ware_id', __('Ware id'));

        return $form;
    }
}
