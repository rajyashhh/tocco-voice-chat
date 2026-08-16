<?php

namespace Modules\RoomBoom\Http\Controllers\web;

use Carbon\Carbon;
use App\Models\Gift;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Admin;
use App\Selectables\Gifts;
use App\Selectables\Wares;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Illuminate\Http\UploadedFile;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\RoomBoomReward;

class RoomBoomRewardController extends MainController
{
    public $permission_name = 'room-boom-rewards';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Room Boom Rewards'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Room Boom Reward'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->title(__('Edit Room Boom Reward'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Create Room Boom Reward'))
            ->body($this->form()));
    }

    

    protected function grid()
    {
        $grid = new Grid(new RoomBoomReward());

        $roomBoomLevelId = request('room_boom_level_id');

        $grid->model()
            ->where('room_boom_level_id', $roomBoomLevelId)
            ->with([
                'ware:id,name,img2,show_img',
                'gift:id,name,show_img,img'
            ])
            ->select([
                'id',
                'target_type',
                'target',
                'priority',
                'quantity',
                'expire_days',
                'created_at',
                'room_boom_level_id'
            ]);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('target_type', __('Target Type'));

        // ✅ Target name / value
        $grid->column('target', __('Target'))->display(function () {
            return match ($this->target_type) {
                'ware'        => $this->ware?->name,
                'gift'        => $this->gift?->name,
                'coin'        => $this->target,
                'achievement' => $this->customAchievement?->name ?? '',
                default => '-'
            };
        });

        // ✅ Image (skip during export)
        if (!request()->filled('_export_')) {
            $grid->column('image', __('Image'))->display(function () {

                $path = match ($this->target_type) {
                    'ware'  => $this->ware?->img2 ?? $this->ware?->show_img,
                    'gift'  => $this->gift?->show_img ?? $this->gift?->img,
                    'achievement' => $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ??  '',
                    'coin'  => 'coin.png',
                    default => null
                };

                if (!$path) {
                    return '-';
                }

                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }

        $grid->column('priority', __('Priority'));
        $grid->column('quantity', __('Quantity'));
        $grid->column('expire_days', __('Expire'));

        // ✅ No Carbon parse per row
        $grid->column('created_at', __('Created At'))
            ->display(fn($v) => substr($v, 0, 10));

        // Extend grid if exists
        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        // Back button
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(
                '<a href="' . admin_url('room_boom_levels') . '" class="btn btn-sm btn-info">
                <i class="fa fa-arrow-left"></i> ' . __('Back') . '
            </a>'
            );
        });

        // Desktop optimization
        \Encore\Admin\Facades\Admin::script("
        if (window.innerWidth >= 1024) {
            $('.table-responsive').removeClass('table-responsive');
        }
    ");

        return $grid;
    }

    protected function detail($id)
    {
        $id = request()->route('id');
        $show = new Show(RoomBoomReward::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('target_type', __('Target Type'));
        $show->field('target', __('target'));
        $show->field('priority', __('priority'));
        $show->field('quantity', __('Quantity'));
        $show->field('expire_days', __('expire'));
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
        $form = new Form(new RoomBoomReward());

        $roomBoomLevelId = request('room_boom_level_id');
        $form->hidden('room_boom_level_id')->default($roomBoomLevelId);

        $form->saving(function (Form $form) use ($roomBoomLevelId) {
            if (!$form->model()->exists) {
                $count = RoomBoomReward::where('room_boom_level_id', $roomBoomLevelId)->count();
                if ($count >= 7) {
                    admin_error(__('You can only have a maximum of 7 gifts for this Room Boom Level.'));
                    return back();
                }
            }
        });

        $form->select('target_type', trans('Target Type'))->options([
            "ware" => __('ware'),
            "gift" => __('gift'),
            "achievement" => __('achievement'),
            "coin" => __('coin'),
        ])
            ->when("ware", function (Form $form) {
                $this->addWareFields($form);
                $form->number('expire_days', __('expire'))->rules('nullable|integer|min:0');
            })
            ->when("gift", function (Form $form) {
                $this->addGiftFields($form);
                $form->number('expire_days', __('expire'))->rules('nullable|integer|min:0');
            })
            ->when("achievement", function (Form $form) {
                $this->addAchievementFields($form);
                $form->number('expire_days', __('expire'))->rules('nullable|integer|min:0');
            })
            ->when("coin", fn(Form $form) => $this->addcoinField($form));

        $form->number('priority', __('priority'))
            ->rules(function () use ($roomBoomLevelId, $form) {
                return [
                    'required',
                    'integer',
                    'min:1',
                    Rule::unique('room_boom_rewards', 'priority')
                        ->where('room_boom_level_id', $roomBoomLevelId)
                        ->ignore($form->model()->id)
                ];
            });
        $form->number('quantity', __('Quantity'))->rules('required|integer|min:1');

        $form->saving(function (Form $form) {
            switch ($form->target_type) {
                case 'ware':
                    $form->model()->target = $form->ware_target_id;
                    break;

                case 'gift':
                    $form->model()->target = $form->gift_target_id;
                    break;

                case 'achievement':

                    $form->model()->target = $form->achievement_target;

                    break;

                case 'coin':
                    $form->model()->target = $form->coin_target;
                    break;
            }

            unset($form->ware_target_id, $form->gift_target_id, $form->achievement_target, $form->coin_target);
        });

        return $form;
    }

    protected function addWareFields($form)
    {
        $form->belongsTo('ware_target_id', WaresByType::class, __('Ware'), function ($form) {
            $form->select('id', __('Wares'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Ware::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);
        })->default(function ($form) {
            return $form->model()->target_type === 'ware'
                ? $form->model()->target
                : null;
        });

        $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');
    }

    protected function addGiftFields($form)
    {
        $form->belongsTo('gift_target_id', Gifts::class, __('Gift'), function ($form) {
            $form->select('id', __('Gifts'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $gift = Gift::find($id);
                    return $gift ? [$gift->id => "{$gift->name}_{$gift->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url'     => admin_url('gifts-by-id')
                ]);
        })->default(function ($form) {
            return $form->model()->target_type === 'gift'
                ? $form->model()->target
                : null;
        });

        $form->html('<div id="gift-image-preview" style="margin-top:10px;"></div>');
    }

    //    protected function addGiftFields($form, $prefix = 'gift_'): void
    //    {
    //        $fieldName = $prefix . 'id';
    //
    //        $form->select('target', __('Gift'))
    //            ->options(function ($id) {
    //                $query = Gift::query()->pluck('name', 'id');
    //
    //                if ($id) {
    //                    $gift = Gift::find($id);
    //                    if ($gift && !$query->has($gift->id)) {
    //                        $query[$gift->id] = "{$gift->name}_{$gift->id}";
    //                    }
    //                }
    //                return Gift::pluck('name', 'id');
    //            })
    //            ->attribute([
    //                'data-image-select' => 1,
    //                'data-load-url'     => admin_url('gifts-by-id'),
    //            ]);
    //        $form->html('<div id="gift-image-preview" style="margin-top:10px;"></div>');
    //
    //        $this->addGiftJs($fieldName, 'gift-image-preview');
    //    }


    protected function addGiftJs(string $fieldName = 'gift_id', string $previewId = 'gift-image-preview'): void
    {
        $script = <<<'JS'
    (function () {
        var select = $('select[name="{{fieldName}}"]');
        var preview = $('#{{previewId}}');

        function updatePreview(id) {
            var url = select.data('load-url');
            if (!url || !id) { preview.empty(); return; }

            $.get(url, { id: id }, function (res) {
                var img  = (res && (res.image || (res.data && res.data.image))) ? (res.image || res.data.image) : null;
                var name = (res && (res.name  || (res.data && res.data.name ))) ? (res.name  || res.data.name)  : '';
                if (img) {
                    preview.html(
                      '<div style="margin-top:8px">' +
                      '<img src="'+ img +'" style="max-width:160px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1)"/>' +
                      '<div style="margin-top:6px;font-size:12px">'+ name +'</div>' +
                      '</div>'
                    );
                } else {
                    preview.html('<small class="text-muted">لا توجد صورة</small>');
                }
            });
        }
        select.on('change', function () { updatePreview($(this).val()); });

        if (select.val()) updatePreview(select.val());
    })();
    JS;

        $script = str_replace(['{{fieldName}}', '{{previewId}}'], [$fieldName, $previewId], $script);

        Admin::script($script);
    }



    protected function addAchievementFields($form): void
    {
        $form->belongsTo('achievement_target', CustomAchievements::class, trans('Custom achievement'));
    }

    protected function addCoinField($form): void
    {
        $form->number('coin_target', __('Coin'))
            ->default(function ($form) {
                return $form->model()->target_type === 'coin'
                    ? (int) $form->model()->target
                    : null;
            });
    }

    public function store()
    {
        $form = $this->form();

        $form->saved(function (Form $form) {
            $roomBoomLevelId = $form->model()->room_boom_level_id;
            admin_toastr(__('Created successfully'));
            return redirect()->to('admin/room_boom_rewards/' . $roomBoomLevelId);
        });

        return $form->store();
    }

    public function update($id)
    {
        $id = request()->route('id');
        $form = $this->form()->edit($id);

        $form->saved(function (Form $form) {
            $roomBoomLevelId = $form->model()->room_boom_level_id;
            admin_toastr(__('Updated successfully'));
            return redirect()->to('admin/room_boom_rewards/' . $roomBoomLevelId);
        });

        return $form->update($id);
    }

    public function destroy($id)
    {
        $reward = RoomBoomReward::findOrFail($id);
        $roomBoomLevelId = $reward->room_boom_level_id;
        $reward->delete();

        admin_toastr(__('Deleted successfully'));

        return [
            'status' => true,
            'message' => __('Deleted successfully'),
            'redirect' => admin_url('room_boom_rewards?room_boom_level_id=' . $roomBoomLevelId),
        ];
    }
}
