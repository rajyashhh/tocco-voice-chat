<?php

namespace Modules\Achievement\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

use App\Helpers\Common;
use App\Enums\ImageType;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\CustomAchievement;
use Modules\Achievement\Entities\CustomAchievementImage;


class CustomAchievementController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'CustomAchievement';

    public $permission_name = 'custom-achievement';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Custom achievement'))
            ->body($this->grid()));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Custom achievement'))
            ->body($this->form()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Custom achievement'))
            ->body($this->form()->edit($id)));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Custom achievement'))
            ->body($this->detail($id)));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CustomAchievement());
        $grid->model()->with('images');

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));

        if (!request()->filled('_export_')) {
            $grid->column('images.image', __('image'))->display(function ($path) {
                $path =   $this->images->firstWhere('language', app()->getLocale())?->image ?? $this->images->firstWhere('language', 'en')?->image;
                /** @var Ware $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
            });
        }
        $grid->disableExport();
        $this->extendGrid($grid);

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
        $show = new Show(CustomAchievement::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
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
        $form = new Form(new CustomAchievement());

        $form->text('name', __('Name'))->required();

        $form->html(function () use ($form) {

            // Get existing images for this badge
            $badgeImages = $form->model()->exists
                ? $form->model()->images()->get()->keyBy('language')
                : collect();

            return view('multiBadges', compact('badgeImages'));
        });

        $form->saved(function (Form $form) {
            // Get uploaded files
            $images = request()->file('images', []);

            // Get input data
            $allData = request()->input('images', []);

            // Load default badge images from DB or newly uploaded file
            $defaultImagePath = $images['default']['image'] ?? null;
            $defaultShowImagePath = $images['default']['default_image'] ?? null;

            // If no uploaded file, use the DB value (existing default)
            $defaultBadgeImage = CustomAchievementImage::where('achievement_id', $form->model()->id)
                ->where('language', 'default')
                ->first();

            $defaultImage = $defaultImagePath
                ? Common::upload('badges', $defaultImagePath)
                : $defaultBadgeImage->image ?? null;

            $defaultShowImage = $defaultShowImagePath
                ? Common::upload('badges', $defaultShowImagePath)
                : $defaultBadgeImage->show_image ?? null;

            foreach ($allData as $lang => $dataInput) {

                $badgeImage = CustomAchievementImage::where('achievement_id', $form->model()->id)
                    ->where('language', $lang)
                    ->first();

                $data = [
                    'achievement_id'   => $form->model()->id,
                    'language'   => $lang,
                    'image_type' => $dataInput['image_type'] ?? ($defaultBadgeImage->image_type ?? ImageType::Image->value),
                ];

                // If the user uploaded a file in this language, use it
                if (!empty($images[$lang]['image'])) {
                    $data['image'] = Common::upload('badges', $images[$lang]['image']);
                } elseif ($lang !== 'default') {
                    // Otherwise, copy default image
                    $data['image'] = $defaultImage;
                } else {
                    // default language
                    $data['image'] = $defaultImage;
                }

                if (!empty($images[$lang]['default_image'])) {
                    $data['show_image'] = Common::upload('badges', $images[$lang]['default_image']);
                } elseif ($lang !== 'default') {
                    $data['show_image'] = $defaultShowImage;
                } else {
                    $data['show_image'] = $defaultShowImage;
                }

                if ($badgeImage) {
                    $badgeImage->update($data);
                } else {
                    CustomAchievementImage::create($data);
                }
            }
        });

        return $form;
    }
}
