<?php

namespace App\Admin\Controllers;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;

class SpecialWareDedicateController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'uuid-dedicate';

    public function index(Content $content)
    {
        return $content
            ->title(trans('uuid dedicate'))
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
            ->title(trans('wares'))
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
            ->title(trans('wares'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('wares'))
            ->body($this->form());
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new Ware);
        $grid->model()->orderByDesc('created_at');
        $grid->model()->whereNotNull('get_type')->where('type', '=', 25);

        $grid->id('ID');
        $grid->column('price', __('price'))->currency()->display(function ($coin) {
            $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                  <span>" . number_format((int)$coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>
                </div>
            ";
        });
        // $grid->column('show_img', __('show_img'))->display(function ($path) {
        //     /** @var Ware $this */
        //     $defaultImage = asset("images/ware-image.jpg");
        //     $url = getImagePath($path) ?? $defaultImage;
        //     if (!isImageExists($url)) {
        //         $url = $defaultImage;
        //     }
        //     return handleShowImageWithTypes($this->id, $url, 50, 50);
        // });
        $grid->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/ware-image.jpg");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
      //  $grid->value(__('value'));


        $grid->column('return', __('dedicate'))->display(function () {

            return (new \App\Admin\Actions\WareDedicateAction($this->id))->render();
        });
        $grid->disableExport();
        $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
}
