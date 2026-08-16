<?php

namespace Modules\Badge\Http\Controllers\web;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Enums\BadgeType;
use App\Enums\ImageType;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use Modules\Badge\Entities\BadgeImage;
use App\Admin\Controllers\MainController;

class BadgeController extends MainController
{
    public $permission_name = 'badges';

    protected $title = 'Badges';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('badges'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('badges'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('badges'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('badges'))
            ->body($this->form()));
    }


    protected function grid()
    {
        $grid = new Grid(new Badge());
        $grid->model()
            ->with('images')->orderBy('priority', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('name', __('name'));
        if (!request()->filled('_export_')) {
            $grid->column('images.image', __('image'))->display(function ($path) {
                $path =   $this->images->firstWhere('language', app()->getLocale())?->image ?? $this->images->firstWhere('language', 'en')?->image;
                /** @var Ware $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
            });
        }
        $grid->column('priority', __('Priority'))->sortable();

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->like('name', __('name'));
            $filter->equal('priority', __('Priority'));
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        $this->extendGrid($grid);
        return $grid;
    }

    /**
     * Make a form builder.
     */
    protected function form()
    {
        $form = new Form(new Badge());

        $form->text('name', __('Name'))
            ->rules('required|unique:badges,name,{{id}}');

        $form->select('type', __('Type'))
            ->options(BadgeType::options())
            ->default(BadgeType::Regular->value)
            ->rules('required|in:' . implode(',', array_keys(BadgeType::options())));
        $form->number('priority', __('Priority'))->min(0)->default(0)->required();

        $form->html(function () use ($form) {

            $badgeImages = collect();

            if (request()->route('badge')) {
                $badge = Badge::with('images')->find(request()->route('badge'));
                $badgeImages = $badge?->images->keyBy('language') ?? collect();
            }

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
            $defaultBadgeImage = BadgeImage::where('badge_id', $form->model()->id)
                ->where('language', 'default')
                ->first();

            $defaultImage = $defaultImagePath
                ? Common::upload('badges', $defaultImagePath)
                : $defaultBadgeImage->image ?? null;

            $defaultShowImage = $defaultShowImagePath
                ? Common::upload('badges', $defaultShowImagePath)
                : $defaultBadgeImage->show_image ?? null;

            foreach ($allData as $lang => $dataInput) {

                $badgeImage = BadgeImage::where('badge_id', $form->model()->id)
                    ->where('language', $lang)
                    ->first();

                $data = [
                    'badge_id'   => $form->model()->id,
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
                    BadgeImage::create($data);
                }
            }
        });






        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Badge::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('name'));
        $show->field('image', __('image'))->display(function ($path) {
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $show->field('image_type', __('image_type'));
        $show->field('type', __('type'));
        $show->field('priority', __('Priority'));
        return $show;
    }
}
