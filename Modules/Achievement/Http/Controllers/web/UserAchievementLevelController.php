<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\AchievementValidImage;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Modules\Achievement\Entities\UserAchievementLevel;



class UserAchievementLevelController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'user_achievement_level';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Achievement Reports'))
            ->body($this->grid()));
    }


    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('user-achievement-levels'))
            ->body($this->form()->edit($id)));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('user-achievement-levels'))
            ->body($this->detail($id)));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserAchievementLevel());
        $countryID = Common::filterCountryIds();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('uuid'));
            });
        });
        $grid->model()->with([
            'user',
            'user.profile',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'achievementLevel',
            'giftAchievement.gift:id,img',
            'customAchievement.images',
        ])->when($countryID, function ($query) use ($countryID) {
            $query->where(function ($q) use ($countryID) {
                $q->whereHas('user', function ($subQuery) use ($countryID) {
                    $subQuery->whereIn('country_id', $countryID);
                });
            });
        });
        $grid->disableCreateButton();
        $grid->column('id', __('Id'));

        $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        
        $grid->column('achievementLevel.target', __('achievement_level_target'))->display(function ($column) {
            if ($this->achievement_level_id != null) {
                return $this->achievementLevel->target ?? 0;
            } elseif ($this->gift_achievement_id  != null) {
                return $this->achievement->target ?? 0;
            }

            return "custom";
        });
        $grid->column('custom_image', __('custom_image'))->display(function ($value) {
            if ($value != null) {
                $image = $value;
            } else {
                // Use eager-loaded relations instead of per-row queries
                $image = $this->achievementLevel?->valid_image
                    ?? $this->giftAchievement?->gift?->img
                    ?? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image
                    ?? null;
            }
            $url = getDriverUrl() . '/' . $image;
            return "<img src='{$url}' width='80' height='80' onerror=\"this.style.display='none'\">";
        });

        $states = [
            'off' => ['value' => 0, 'text' => 'no', 'color' => 'danger'],
            'on' => ['value' => 1, 'text' => 'yes', 'color' => 'success'],
        ];
        $grid->column('is_enable')->switch($states);


        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableView();
            $actions->disableEdit();
        });

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
        $show = new Show(UserAchievementLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('achievement_level_id', __('Achievement level id'));
        $show->field('user_id', __('User id'));
        $show->field('gift_achievement_id', __('Gift achievement id'));
        $show->field('unique_value', __('Unique value'));
        $show->field('end_at', __('End at'));
        $show->field('is_enable', __('Is enable'));
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
        $form = new Form(new UserAchievementLevel());

        // $form->select('user_id', __('user'))->options('/api/search/users2')->ajax('/api/search/users2', 'id', 'name');
        // $form->select('achievement', __('Achievement'))->options(Achievement::where('type','!=','gift_target')->pluck('name', 'id'));


        // $form->select('achievementLevel', __('Achievement level id'));


        // $form->number('user_id', __('User id'));
        // // $form->select('achievement_level_id', __('Achievement'))
        // ->options(\Modules\Achievement\Entities\::pluck('name', 'id'));
        // $form->number('gift_achievement_id', __('Gift achievement id'));
        $form->switch('is_enable', trans('enable'))->states(Common::getSwitchStates());

        return $form;
    }

    public function create(Content $content)
    {
        $achievementValidImage = AchievementValidImage::where('user_id', Auth::user()->id)->get();
        return parent::create($content
            ->title(trans('user-achievement-levels'))
            ->body(view('admin.grid.users.UserAchievementLevel', compact('achievementValidImage'))));
    }
}
