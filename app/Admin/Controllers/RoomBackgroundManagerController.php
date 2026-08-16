<?php

namespace App\Admin\Controllers;

use App\Models\Background;
use App\Models\Room;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class RoomBackgroundManagerController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'rooms';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Background Manager'))
            ->description('Manage room cover images and backgrounds')
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title('Room Background')
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title('Edit Room Background')
            ->body($this->form()->edit($id)));
    }

    protected function grid()
    {
        $grid = new Grid(new Room);

        $grid->model()->with('owner')->orderByDesc('id');

        $grid->disableCreateButton();
        $grid->disableExport();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('room_name', 'Room Name');
            $filter->equal('id', 'Room ID');
            $filter->equal('numid', 'Room NumID');
        });

        $grid->column('id', 'ID')->sortable();
        $grid->column('numid', 'NumID');
        $grid->column('room_name', 'Room Name');

        $grid->column('room_cover', 'Room Cover')->display(function ($cover) {
            if (empty($cover)) {
                return '<span style="color:#999;">No cover</span>';
            }
            $url = getImagePath($cover);
            return "
                <img src='{$url}' style='width:60px; height:60px; border-radius:5px; cursor:pointer; object-fit:cover;'
                     onclick='openBgModal(\"{$url}\")' />
            ";
        });

        $grid->column('final_room_image', 'Background')->display(function () {
            $bgImg = $this->backgroundImage?->img
                ?? $this->background?->img
                ?? null;

            if (empty($bgImg)) {
                return '<span style="color:#999;">Default</span>';
            }

            $url = getImagePath($bgImg);
            return "
                <img src='{$url}' style='width:60px; height:60px; border-radius:5px; cursor:pointer; object-fit:cover;'
                     onclick='openBgModal(\"{$url}\")' />
            ";
        });

        $grid->column('room_background', 'BG ID');

        $grid->column('uid', 'Owner')->display(function ($uid) {
            $user = $this->owner;
            return $user ? ($user->name ?? $user->uuid) : $uid;
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->header(function () {
            return "
                <div id='bgModalOverlay' style='display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); text-align:center; cursor:pointer;' onclick='closeBgModal()'>
                    <span style='position:absolute; top:15px; right:25px; font-size:35px; color:white; cursor:pointer;'>&times;</span>
                    <img id='bgModalImg' style='max-width:90%; max-height:90%; margin-top:50px; border-radius:8px;' />
                </div>
                <script>
                    function openBgModal(src) {
                        document.getElementById('bgModalImg').src = src;
                        document.getElementById('bgModalOverlay').style.display = 'block';
                    }
                    function closeBgModal() {
                        document.getElementById('bgModalOverlay').style.display = 'none';
                    }
                </script>
            ";
        });

        $this->extendGrid($grid);

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Room::findOrFail($id));
        $show->field('id', 'ID');
        $show->field('room_name', 'Room Name');
        $show->field('room_cover', 'Room Cover');
        $show->field('room_background', 'Background ID');
        return $show;
    }

    protected function form()
    {
        $form = new Form(new Room);
        $this->disableFormTools($form);

        $form->display('id', 'ID');
        $form->display('room_name', 'Room Name');

        $form->image('room_cover', 'Room Cover')
            ->disk('gcs')
            ->dir('rooms')
            ->uniqueName()
            ->removable();

        $form->select('room_background', 'Preset Background')
            ->options(function () {
                return Background::where('enable', 1)
                    ->pluck('id', 'id')
                    ->mapWithKeys(function ($id) {
                        return [$id => "Background #{$id}"];
                    });
            })
            ->default(null);

        $form->divider('Current Background Preview');

        $form->html(function () use ($form) {
            $room = Room::find(request()->route('room_background_manager'));
            if (!$room) return '';

            $coverUrl = $room->room_cover ? getImagePath($room->room_cover) : null;
            $bgImg = $room->backgroundImage?->img ?? $room->background?->img ?? null;
            $bgUrl = $bgImg ? getImagePath($bgImg) : null;

            $coverHtml = $coverUrl
                ? "<img src='{$coverUrl}' style='max-width:200px; max-height:200px; border-radius:8px; border:2px solid #ddd;' />"
                : '<span style="color:#999;">No cover image</span>';

            $bgHtml = $bgUrl
                ? "<img src='{$bgUrl}' style='max-width:200px; max-height:200px; border-radius:8px; border:2px solid #ddd;' />"
                : '<span style="color:#999;">Default background</span>';

            return "
                <div style='display:flex; gap:30px; align-items:flex-start;'>
                    <div style='text-align:center;'>
                        <h5>Room Cover</h5>
                        {$coverHtml}
                    </div>
                    <div style='text-align:center;'>
                        <h5>Room Background</h5>
                        {$bgHtml}
                    </div>
                </div>
            ";
        });

        $form->saving(function (Form $form) {
            if ($form->room_cover === null && $form->model()->room_cover) {
                // Cover was removed
            }
        });

        return $form;
    }
}
