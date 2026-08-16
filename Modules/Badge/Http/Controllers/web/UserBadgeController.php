<?php

namespace Modules\Badge\Http\Controllers\web;


use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\UserBadge;
use App\Admin\Controllers\MainController;

class UserBadgeController extends MainController
{
    public $permission_name = 'dedicate-user-badges';

    protected $title = 'Badges';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('badges'))
            ->body($this->grid()));
    }

    protected function grid()
    {
        $grid = new Grid(new UserBadge());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'));


        $grid->column('user.name', trans('owner'))->display(function ($name) {

            $uid = @$this->user->uuid;
            if ($this->user && request()->filled('_export_')) {
                return $name . '-' . $uid;
            }
            $path = @$this->user->profile?->avatar;
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this->user ? url("admin/users/{$this->user->id}") : 0;
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });
        if (!request()->filled('_export_')) {
            $grid->column('badge.image', __('image'))->display(function ($path) {
                /** @var Ware $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('receive_type', __('receive_type'));
        $grid->column('expire', __('receive_type'))->display(function ($expire) {
            return \Carbon\Carbon::parse($expire)->format('Y-m-d H:i:s');
        });
        $grid->column('created_at', __('created_at'));
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->disableIdFilter();


            $filter->where(function ($query) {
                $query->whereHas('user', function ($subQuery) {
                    $subQuery->where('uuid', 'like', "%{$this->input}%");
                });
            }, __('UUID'))->placeholder(__('search for host by UUID'));
        });


       
        $grid->tools(function (Grid\Tools $tools) {
            $url = url('admin/dedicate-badges');
            $customButtonHTML = <<<HTML
                     <div style="display: contents; align-items: center;">
                        <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                            <i class="fa fa-arrow-left"></i> الرجوع
                        </a>
                    </div>
                HTML;
            $tools->append($customButtonHTML);
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


        return $grid;
    }
}
