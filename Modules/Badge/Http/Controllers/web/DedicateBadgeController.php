<?php

namespace Modules\Badge\Http\Controllers\web;


use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Admin\Controllers\MainController;
use Encore\Admin\Facades\Admin;

class DedicateBadgeController extends MainController
{
    public $permission_name = 'dedicate-badges';

    protected $title = 'Badges';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('badges'))
            ->body($this->grid()));
    }



    protected function grid()
    {
        $grid = new Grid(new Badge());
        $lang = app()->getLocale();
        $grid->model()->whereHas('images', function ($query) use ($lang) {
            $query->where('language', $lang);
        })->with('images')->orderBy('priority', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('name', __('name'));
        if (!request()->filled('_export_')) {
            $grid->column('images.image', __('image'))->display(function ($path) {
                $path =   $this->images->firstWhere('language', app()->getLocale())?->image;
                /** @var Ware $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
            });
        }

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->like('name', 'name');
            $filter->equal('priority', 'Priority');
        });

        if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('return', __('dedicate'))->display(function () {

                return (new \App\Admin\Actions\BadgeDedicateAction($this->id))->render();
            });
        }

        $grid->tools(function (Grid\Tools $tools) {

            $tools->append('<a href="' . url('/admin/user-badges') . '"  class="btn btn-sm btn-success">' . __('admin.history') . '</a>');
        });

        $grid->disableExport();
        $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->disableRowSelector();

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }
}
