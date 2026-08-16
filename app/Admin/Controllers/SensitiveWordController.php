<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\SensitiveWord;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;


class SensitiveWordController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Sensitive Word';
    public $permission_name = 'sensitive-word';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans($this->title))
            ->body($this->grid()));
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
        return parent::show($id, $content
            ->title(trans($this->title))
            ->body($this->detail($id)));
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
        return parent::edit($id, $content
            ->title(trans($this->title))
            ->body($this->form($id)->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans($this->title))
            ->body($this->form()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SensitiveWord());

        $grid->column('id', __('Id'));
        $grid->column('word', __('Word'))->display(function ($value) {
            $locale = \Illuminate\Support\Facades\App::getLocale();
            return $value[$locale] ?? ($value['ar'] ?? '');
        });
        $grid->column('replacement', __('Replacement'));
        $grid->column('severity', __('Severity'));
        $grid->column('action', __('Action'));
        $grid->column('is_active', __('Is active'))->switch(Common::getSwitchStates());
        

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
        $show = new Show(SensitiveWord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('word', __('Word'));
        $show->field('replacement', __('Replacement'));
        $show->field('severity', __('Severity'));
        $show->field('action', __('Action'));
        $show->field('is_active', __('Is active'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        $form = new Form(new SensitiveWord());

        // Multi-language word input (title tabs → saved into `word` JSON column)
        $form->html(view('multi_lang_bad_words_tabs', [
            'model' => $id != null ? SensitiveWord::find($id) : [],
        ]));

        $form->text('replacement', __('Replacement'))->default('***');
        $form->select('severity', __('Severity'))->options([
            'low'      => __('Low'),
            'medium'   => __('Medium'),
            'high'     => __('High'),
            'critical' => __('Critical'),
        ])->default('medium');
        $form->select('action', __('Action'))->options([
            'filter' => __('Filter'),
            'warn'   => __('Warn'),
            'block'  => __('Block'),
        ])->default('filter');
        $form->switch('is_active', __('Is active'))->states(Common::getSwitchStates())->default(1);

        // Save multi-lang word into the `word` JSON column
        $form->saving(function (Form $form) {
            $words = request()->input('word', []);
            if (!empty($words)) {
                $form->model()->word = $words;
            }
        });

        return $form;
    }
}
