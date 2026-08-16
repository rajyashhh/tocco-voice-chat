<?php

namespace Modules\Region\Http\Controllers;

use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use App\Models\SuperAdminNotification;
use Carbon\Carbon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid());
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
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperAdminNotification());

        $grid->model()->when(request('from_date') && request('to_date'), function ($query,) {

            $start = Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))->startOfDay();
            $end   = Carbon::parse(convertArabicToEnglishNumbers(request('to_date')))->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        });
        $grid->column('id', __('ID'))->sortable();

        $grid->column('title', __('Title'))->display(function ($title) {
            return '<strong>' . e(__($title)) . '</strong>';
        });

        $grid->column('message', __('Message'))->display(function ($message) {
            $data = is_array($this->data) ? $this->data : json_decode($this->data, true);

            $translated = __(
                "{$message}",
                [
                    'name' => $data['requested_by'] ?? 'غير معروف',
                    'id' => $data['requested_by_id'] ?? 0,
                    'coins' => $data['coins_deducted'] ?? 0,
                ]
            );

            return e(Str::limit($translated, 60));
        });

        $grid->column('data', __('Details'))->display(function ($data) {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (!$data || !is_array($data)) {
                return '-';
            }

            $requestedBy = $data['requested_by'] ?? '-';
            $requestedById = $data['requested_by_id'] ?? '-';
            $previewUrl = $data['preview_url'] ?? null;

            $html = "<ul style='padding-left:15px; margin:0; list-style:none;'>";

            $html .= "</ul>";

            if ($previewUrl) {
                try {
                    $enum = \App\Enums\SuperAdminNotificationLink::tryFrom($previewUrl);
                    if ($enum) {
                        $previewUrl = $enum->url($data);
                    }
                } catch (\Throwable $e) {
                }

                $html .= "<div style='margin-top:6px; text-align:center;'>
                            <a href='" . e($previewUrl) . "'
                               class='btn btn-sm btn-outline-primary'
                               target='_blank'>
                               " . __('عرض') . "
                            </a>
                          </div>";
            }

            return $html;
        });

        $grid->column('is_read', __('Read'))->bool();
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $locale = App::getLocale();
            $carbonDate->locale($locale);
            return $carbonDate->translatedFormat('d F Y H:i');
        });
        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {}, __('From Date'), 'from_date')->date()->default(convertArabicToEnglishNumbers(request('from_date')));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {}, __('To Date'), 'to_date')->date()->default(convertArabicToEnglishNumbers(request('to_date')));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->like('title', __('Title'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('is_read', __('Read'))->select([
                    1 => __('Yes'),
                    0 => __('No'),
                ]);
            });
        });

        $grid->disableCreateButton();
        $grid->disableRowSelector();

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
        $show = new Show(AdminNotification::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('type', __('Type'));
        $show->field('title', __('Title'));
        $show->field('message', __('Message'));
        $show->field('data', __('Data'))->unescape()->as(function ($val) {
            $data = is_string($val) ? json_decode($val, true) : $val;
            if (!$data) return '-';

            $html = '<table class="table table-bordered" style="width:100%; background:#fafafa;">';
            foreach ($data as $key => $value) {
                $html .= "<tr><th style='width:30%; text-transform:capitalize;'>{$key}</th><td>" . e($value) . "</td></tr>";
            }
            $html .= '</table>';

            return $html;
        });

        $show->field('is_read', __('Is Read'))->as(fn($v) => $v ? __('Yes') : __('No'));
        $show->field('admin_id', __('Admin ID'));
        $show->field('read_at', __('Read At'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminNotification);


        return $form;
    }
}
