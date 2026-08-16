<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Helpers\Common;
use App\Models\AchievementValidImage;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Enums\TargetType;
use Modules\Achievement\Entities\AchievementLevel;
use Encore\Admin\Facades\Admin;

class AchievementsLevelsController extends MainController
{
    public $permission_name = 'achievement_level';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    public function create(Content $content)
    {
        return parent::create($content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form()));
    }

    public function update($id)
    {
        $id = request()->route('id');
        return $this->form()->update($id);
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id)));
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid()));
    }


    protected function grid()
    {
        $grid = new Grid(new AchievementLevel());
        $achievement_id = request('achievement_id');

        $grid->model()->where('achievement_id', $achievement_id)->orderByDesc('target');

        $grid->column('id', __('Id'));
        $grid->column('achievement.type', __('Achievement'));
        $grid->column('target', __('Target'));
        $grid->column('target_type', __('Target type'));

        $grid->column('valid_image', __('Valid image'))->display(function ($path) {
            $url = getImagePath($path);
            $mediaHtml = handleShowImageWithTypes($this->id, $url, 50, 50);

            return '<div style="direction:ltr;">' . $mediaHtml . '</div>';
        });
        $grid->column('invalid_image', __('Invalid image'))->display(function ($path) {
            /** @var Ware $this */
            $url = getImagePath($path);
            $mediaHtml = handleShowImageWithTypes($this->id, $url, 50, 50);

            return '<div style="direction:ltr;">' . $mediaHtml . '</div>';
        });
        $grid->column('ar_description', __('ar_description'));
        $grid->column('en_description', __('en_description'));

        $grid->disableExport();
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
        $show = new Show(AchievementLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('achievement_id', __('Achievement id'));
        $show->field('target', __('Target'));
        $show->field('target_type', __('Target type'));
        $show->field('valid_image', __('Valid image'));

        $show->field('invalid_image', __('Invalid image'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     *
     *
     */
    protected function form()
    {
        $form = new Form(new AchievementLevel());
        $this->disableFormTools($form);

        $form->hidden('achievement_id')->value(request('achievement_id'));
        $form->number('target', __('Target'))->rules('required');
        $form->select('target_type', __('Target type'))->options(function ($value) {
            return TargetType::getTranslatedOptions();
        })->rules('required');
        $form->file('valid_image', trans('Valid image'))->name(function ($file) {
            return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        })->rules('required');
        $form->file('invalid_image', trans('Invalid image'))->name(function ($file) {
            return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        })->rules('required');
        $form->select('image_type', __('image_type'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
                'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),

            ]
        )->required();
        $form->textarea('ar_description', __('ar_description'));
        $form->textarea('en_description', __('en_description'));
        $form->saving(function (Form $form) {
            if (request()->hasFile('valid_image')) {
                $file = request()->file('valid_image');
                $validImagePath = Common::upload('achievementValidImages', $file);
                $form->model()->valid_image = $validImagePath;
            }

            // if ($form->model()->valid_image) {
            //     AchievementValidImage::create([
            //         'image' => $form->model()->valid_image, //
            //     ]);
            // }
            if ($form->model()->valid_image) {
                $existingImage = AchievementValidImage::where('image', $form->model()->valid_image)->first();

                if (!$existingImage) {
                    AchievementValidImage::create([
                        'image' => $form->model()->valid_image,
                    ]);
                }
            }
        });
        return $form;
    }
}
