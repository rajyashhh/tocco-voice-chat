<?php

namespace Modules\RoomBoom\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use Carbon\Carbon;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\RoomBoom\Entities\RoomBoomLevel;

class RoomBoomLevelController extends MainController
{
    public $permission_name = 'room-boom-levels';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Room Boom Levels'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Room Boom Level'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__('Edit Room Boom Level'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Create Room Boom Level'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new RoomBoomLevel());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('level', __('level'));
        $grid->column('min_target', __('min target'));
        $grid->column('target', __('target'));
        if (!request()->filled('_export_')) {
            $grid->column('video', __('video'))->display(function ($path) {
                $defaultImage = asset("images/image.png");

                $url = getImagePath($path) ?? $defaultImage;
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        if (Admin::user()->can('browse-room-boom-rewards') || Admin::user()->can('*')) {
            if (!request()->filled('_export_')) {
                $grid->column(__('Procedures'))->display(function () {
                    $url = url('admin/room_boom_rewards/' . $this->id);
                    $text = __('Room Boom Rewards');
                    return "<a href='{$url}' class='btn btn-sm btn-info'>{$text}</a>";
                });
            }
        }


        if (Admin::user()->can('edit-' . 'room-boom-levels') || Admin::user()->can('*')) {
            $grid->column(__('Theme'))->display(function () {

                $url1 = url('admin/room_boom-theme/' . $this->id);

                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('Theme') . "</a>";
                return $button1;
            });
        }
        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(RoomBoomLevel::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('level', __('level'));
        $show->field('min_target', __('target'));
        $show->field('target', __('target'));
        $show->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $show->column('updated_at', __('Updated At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new RoomBoomLevel());

        $form->number('level', __('level'))->rules('required|integer|min:1')
            ->help(__('Represents the stage or rank of the Room Boom.'));
        $form->number('min_target', __('min target'))->required()
            ->help(__('The minimum required gifts to activate the Boom Room at this level.'));
        $form->number('target', __('target'))->required()
            ->help(__('The full target that must be achieved to complete the Room Boom at this level.'));
        $form->file('video', trans('video'))->name(function ($file) {
            $extension = $file->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $file->guessExtension();
            }
            return now()->timestamp . rand(0, 999) . '.' . $extension;
        })->default('1.png')
            ->help(__('The special video for this level, displayed after completion. Each level has its own unique video.'));
        $form->select('image_type', __('image_type'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
            ]
        )->required();
        $form->saving(function (Form $form) {
            if (!$form->model()->exists) {
                $count = RoomBoomLevel::count();
                if ($count >= 5) {
                    $error = __('You can only have a maximum of 5 Room Boom Levels.');
                    admin_error($error);
                    return back();
                }
            }
        });

        return $form;
    }

    public function editBackgroundImage($id, Content $content)
    {
        $roomBoomLevel = RoomBoomLevel::findOrFail($id);
        return parent::edit($id, $content
            ->title(trans('Edit Room Boom Level'))
            ->body($this->backgroundImage($roomBoomLevel)->edit($id)));
    }

    protected function backgroundImage($model = null)
    {
        if ($model === null) {
            $model = new RoomBoomLevel();
        }

        $form = new Form($model);

        // ✅ IMPORTANT: SET FORM ACTION HERE
        $form->setAction(admin_url('room_boom-theme/' . $model->id));

        // Disable default tools (view, delete, etc.)
        $this->disableFormTools($form);

        // Background image upload
        $form->file('background_image', trans('background image'))->name(function ($file) {
            $extension = $file->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $file->guessExtension();
            }
            return 'img_' . now()->timestamp . '_' . rand(100, 999) . '.' . $extension;
        });
        $form->select('image_type_background', __('image type background'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
                'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),

            ]
        )->required();

        $form->file('boom_image', trans('boom image'))->name(function ($file) {
            $extension = $file->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $file->guessExtension();
            }
            return 'img_' . now()->timestamp . '_' . rand(100, 999) . '.' . $extension;
        });
        $form->select('image_type_boom', __('image type boom'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
                'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),

            ]
        )->required();
        // After save
        $form->saved(function (Form $form) {
            admin_toastr(__('Background image updated successfully'), 'success');
            return redirect(admin_url('room_boom_levels'));
        });

        return $form;
    }


    public function updateBackgroundImage($roomBoomLevel_id)
    {
        Permission::check('edit-' . $this->permission_name);

        $roomBoomLevel = RoomBoomLevel::findOrFail($roomBoomLevel_id);

        if (request()->hasFile('background_image')) {

            $image = Common::upload('images', request()->file('background_image'));

            $roomBoomLevel->background_image = $image;
        }

        if (request()->hasFile('boom_image')) {

            $image = Common::upload('images', request()->file('boom_image'));

            $roomBoomLevel->boom_image = $image;
        }
        $roomBoomLevel->image_type_background = request()->input('image_type_background');
        $roomBoomLevel->image_type_boom = request()->input('image_type_boom');

        $roomBoomLevel->save();

        settings()->set('boom_themes', time());

        admin_toastr(__('Saved successfully'), 'success');

        return redirect(admin_url('room_boom_levels'));
    }
}
