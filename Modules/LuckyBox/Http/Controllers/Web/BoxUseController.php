<?php

namespace Modules\LuckyBox\Http\Controllers\Web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\LuckyBox\Entities\BoxUse;

class BoxUseController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'box-use';
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->header(trans('Dumped boxes'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    // protected function grid0()
    // {
    //     $grid = new Grid(new BoxUse);

    //     $grid->id( __ ('ID'));
    //     $grid->box_id( __ ('box_id'));
    //     $grid->user_id(__ ('user_id'));
    //     $grid->coins(__ ('coins'));
    //     $grid->end_at(__ ('end_at'));
    //     $grid->room_uid(__ ('room_uid'));
    //     $grid->room_id(__ ('room_id'));
    //     $grid->users_num(__ ('users_num'));
    //     $grid->type(__ ('type'));
    //     $grid->label(__ ('label'));
    //     $grid->used_num(__ ('used_num'));
    //     $grid->not_used_num( __ ('not_used_num'));
    //     $grid->disableCreateButton ();
    //     $grid->disableExport();
    //     return $grid;
    // }


    protected function grid()
    {
        $grid = new Grid(new BoxUse);
        $countryID = Common::filterCountryIds();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->disableIdFilter();


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
            'room',
            'room.roomVisitors'
        ])->when($countryID, function ($query) use ($countryID) {
            $query->where(function ($q) use ($countryID) {
                $q->whereHas('user', function ($subQuery) use ($countryID) {
                    $subQuery->whereIn('country_id', $countryID);
                })
                    ->orWhereHas('room.owner', function ($subQuery) use ($countryID) {
                        $subQuery->whereIn('country_id', $countryID);
                    });
            });
        })->orderByDesc('id');

        $grid->id(__('ID'));

        $grid->column('nameName', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('box_id', __('Box'))->display(function () {


            $name = $this->type == 0 ? __('normal') : __('super');


            return "<a  style='text-decoration: none;'>
                        <div style='display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; background: var(--bg-color);'>
                            <span style='color: var(--primary-color); font-weight: bold;'>$name</span>
                        </div>
                    </a>";
        });

        $grid->column('room_id', __('Room'))->display(function () {
            $room = $this->room;
            if (!$room) return '-';

            $name = $room->room_name ?? 'Unknown Room';
            $roomUrl = admin_url('rooms/' . $room->id);

            $defaultImage = asset("images/default-room.jpg");
            $roomImagePath = $room->room_cover ?? null;
            $roomImage = $roomImagePath ? getImagePath($roomImagePath) : $defaultImage;

            if (!isImageExists($roomImage)) {
                $roomImage = $defaultImage;
            }

            return "<a href='$roomUrl' style='text-decoration: none;'>
                        <div style='display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; background: var(--bg-color);'>
                            <img src='$roomImage' alt='Room Image' style='width: 40px; height: 40px; border-radius: 8px;'>
                            <span style='color: var(--primary-color); font-weight: bold;'>$name</span>
                        </div>
                    </a>";
        });



        $grid->column('coins', __('coins'))->display(function ($coins) {

            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$coins}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('end_at', __('end_at'))->display(function ($value) {
            $start = \Carbon\Carbon::parse($this->created_at);
            $end   = \Carbon\Carbon::parse($value);

            if ($this->type == 1) {
                // الفرق بالدقايق
                $diff = $end->diffInMinutes($start);
                return $diff . ' ' . __('minutes');
            } elseif ($this->type == 0) {
                // الفرق بالساعات
                $diff = $end->diffInHours($start);
                return $diff . ' ' . __('hours');
            }

            return '-';
        });

        $grid->users_num(__('users_num'));
        // $grid->column('type', __('Type'))->display(function ($value) {
        //     return $value == 1 ? __('type_global') : __('type_local');
        // });
        $grid->label(__('label'));
        $grid->used_num(__('used_num'));
        $grid->not_used_num(__('not_used_num'));

        $grid->disableCreateButton();
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
        $show = new Show(BoxUse::findOrFail($id));

        $show->id('ID');
        $show->box_id('box_id');
        $show->user_id('user_id');
        $show->coins('coins');
        $show->end_at('end_at');
        $show->room_uid('room_uid');
        $show->room_id('room_id');
        $show->users_num('users_num');
        $show->type('type');
        $show->label('label');
        $show->used_num('used_num');
        $show->not_used_num('not_used_num');
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BoxUse);
        $this->disableFormTools($form);


        $form->display('ID');
        $form->text('box_id', 'box_id');
        $form->text('user_id', 'user_id');
        $form->text('coins', 'coins');
        $form->text('end_at', 'end_at');
        $form->text('room_uid', 'room_uid');
        $form->text('room_id', 'room_id');
        $form->text('users_num', 'users_num');
        $form->text('type', 'type');
        $form->text('label', 'label');
        $form->text('used_num', 'used_num');
        $form->text('not_used_num', 'not_used_num');
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
