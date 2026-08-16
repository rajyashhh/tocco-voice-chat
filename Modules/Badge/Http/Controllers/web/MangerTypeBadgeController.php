<?php

namespace Modules\Badge\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Models\MangerType;
use App\Selectables\Badges;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Admin\Controllers\MainController;
use Modules\Badge\Jobs\SyncMangerTypeBadgesJob;
use Modules\Badge\Entities\MangerTypeBadge;
use Modules\Badge\Actions\DeleteMangerTypeBadge;
use Encore\Admin\Controllers\HasResourceActions;

class MangerTypeBadgeController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'manger-type-badges';

    protected $title = 'Badges';

    public function index(Content $content, $mangerTypeId = null)
    {
        $mangerType = MangerType::findOrFail($mangerTypeId);

        return parent::index($content
            ->header(__('manger-types') . ' - ' . ($mangerType->name_en ?? $mangerType->name_ar))
            ->description(trans('id') . $mangerType->id)
            ->body($this->grid($mangerTypeId)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->header(trans('admin.create'))
            ->body($this->form()));
    }

    public function store()
    {
        return $this->form()->store();
    }

    protected function grid($mangerTypeId)
    {
        $grid = new Grid(new MangerTypeBadge());
        $grid->model()->where('manger_type_id', $mangerTypeId)->orderBy('id', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('badge.name', __('name'));
        if (!request()->filled('_export_')) {
            $grid->column('badge.image', __('image'))->display(function () {
                $path = $this->badge?->images?->firstWhere('language', app()->getLocale())?->image
                    ?? $this->badge?->images?->first()?->image
                    ?? $this->badge?->image;
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('expire', __('expire'))->display(function ($expire) {
            return $expire ?: '-';
        });

        $grid->tools(function (Grid\Tools $tools) {
            $url = url('admin/manger-types');
            $back = __('back');
            $tools->append('<a href="' . $url . '" class="btn btn-sm btn-info" style="margin-right:10px;"><i class="fa fa-arrow-left"></i> ' . $back . '</a>');
        });

        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            $actions->add(new DeleteMangerTypeBadge());
        });

        Admin::script("
            if (window.innerWidth >= 1024) {
                $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new MangerTypeBadge());
        $this->disableFormTools($form);

        $form->hidden('manger_type_id')->value(request('manger_type_id'));
        $form->belongsTo('badge_id', Badges::class, __('Badges'))->rules('required');
        $form->number('expire', __('expire'))->min(0)->default(0);

        $form->saving(function (Form $form) {
            $exists = MangerTypeBadge::where('manger_type_id', $form->manger_type_id)
                ->where('badge_id', $form->badge_id)
                ->exists();
            if ($exists) {
                $error = new \Illuminate\Support\MessageBag([
                    'badge_id' => [__('This badge is already linked to this position.')],
                ]);
                return back()->withInput()->withErrors($error);
            }
        });

        $form->saved(function (Form $form) {
            SyncMangerTypeBadgesJob::dispatch((int) $form->model()->manger_type_id);
        });

        return $form;
    }
}