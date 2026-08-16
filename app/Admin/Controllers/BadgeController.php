<?php

namespace App\Admin\Controllers;

use App\Models\Badge;
use App\Models\Language;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\UploadedFile;

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

        $grid->model()->orderBy('priority', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('default_image', __('Default Image'))->display(function ($image) {
            $defaultImage = asset("images/background_room.jpg");
            $path = getImagePath($image);
            if (!isImageExists(@$path)) {
                $path = $defaultImage;
            }
            $parsedUrl = parse_url($path);
            $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");
            return "<img src='{$correctUrl}' style='max-height:40px;max-width:40px;' />";
        });
        $grid->column('priority', __('Priority'))->sortable();

        $grid->column('localized_images', __('Localized Images'))->display(function ($images) {
            if (is_string($images)) {
                $images = json_decode($images, true);
            }

            if (!is_array($images) || empty($images)) {
                return '-';
            }

            $defaultImage = asset("images/background_room.jpg");
            $output = '';
            foreach ($images as $lang => $image) {
                if (!is_string($image) || empty($image)) continue;
                $path = getImagePath($image);
                if (!isImageExists(@$path)) {
                    $path = $defaultImage;
                }
                $parsedUrl = parse_url($path);
                $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");
                $safeLang = e($lang);
                $output .= "<strong>{$safeLang}:</strong> <img src='{$correctUrl}' style='max-height:40px;max-width:40px;' /> <br>";
            }

            return $output ?: '-';
        });

        $grid->filter(function ($filter) {
            $filter->like('name', 'Name');
            $filter->equal('priority', 'Priority');
        });

        $grid->actions(function ($actions) {
        });

        $grid->paginate(20);

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }

    /**
     * Make a form builder.
     */
    protected function form()
    {
        $form = new Form(new Badge());

        $form->text('name', __('Badge Name'))->required()->rules(function($form) {
            return 'required|unique:badges,name,' . ($form->model()->id ?? 'NULL');
        });

        $form->image('default_image', __('Default Image'))
            ->removable()
            ->uniqueName()
            ->required();

        $form->number('priority', __('Priority'))->min(0)->default(0)->required();

        $languages = Language::where('is_enabled', 1)->get();

        foreach ($languages as $language) {
            $langCode = $language->code;
            $langName = $language->name;

            $form->image("localized_images.{$langCode}", __('Image for')."{$langName} ({$langCode})")
                ->removable()
                ->uniqueName();
        }

        $form->saving(function (Form $form) {
            $localizedImagesInput = $form->localized_images;
            $storedImages = [];

            if (is_array($localizedImagesInput)) {
                foreach ($localizedImagesInput as $langCode => $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('images');
                        $storedImages[$langCode] = $path;
                    } elseif (is_string($file)) {
                        $storedImages[$langCode] = $file;
                    }
                }
            }

            $form->model()->localized_images = $storedImages;
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Badge::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('default_image', __('Default Image'))->as(function ($image) {
            $defaultImage = asset("images/background_room.jpg");
            $path = getImagePath($image);
            if (!isImageExists(@$path)) {
                $path = $defaultImage;
            }
            $parsedUrl = parse_url($path);
            $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");
            return "<img src='{$correctUrl}' style='max-width:150px;max-height:150px;'/>";
        })->unescape();

        $show->field('priority', __('Priority'));

        $show->field('localized_images', __('Localized Images'))->as(function ($images) {
            if (is_string($images)) {
                $images = json_decode($images, true);
            }
            if (!is_array($images) || empty($images)) {
                return '-';
            }

            $defaultImage = asset("images/background_room.jpg");
            $output = '';
            foreach ($images as $lang => $image) {
                if (!is_string($image) || empty($image)) continue;
                $path = getImagePath($image);
                if (!isImageExists(@$path)) {
                    $path = $defaultImage;
                }
                $parsedUrl = parse_url($path);
                $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");
                $safeLang = e($lang);
                $output .= "<strong>{$safeLang}:</strong> <img src='{$correctUrl}' style='max-height:40px;max-width:40px;' /> <br>";
            }
            return $output ?: '-';
        })->unescape();

        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }
}
