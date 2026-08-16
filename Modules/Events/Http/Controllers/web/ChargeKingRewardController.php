<?php

namespace Modules\Events\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Models\Ware;
use App\Selectables\Badges;
use App\Selectables\CustomAchievements;
use App\Selectables\WaresByType;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use Modules\Events\Entities\ChargeKingReward;
use Modules\Vip\Entities\OVip;

class ChargeKingRewardController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'charge-king';

    public function index(Content $content)
    {
        try {
            Permission::check('browse-' . $this->permission_name);
        } catch (\Exception $e) {
            \Log::warning('Permission denied for user ' . auth()->id() . ' on ' . $this->permission_name);
            abort(403, 'Unauthorized access');
        }

        $url = url('/admin/charge-king');
        $back = __(' back');

        $buttonHTML = <<<HTML
    <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
        <i class="fa fa-arrow-left"></i> {$back}
    </a>
    HTML;

        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->row($buttonHTML)
            ->row($this->rankGrid(1))
            ->row($this->rankGrid(2))
            ->row($this->rankGrid(3));
    }

    public function create(Content $content)
    {
        Permission::check('create-' . $this->permission_name);

        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return $this->form()->store();
    }

    public function update($id)
    {
        Permission::check('edit-' . $this->permission_name);

        $id = request()->route('id');

        return $this->form()->update($id);
    }

    public function edit($id, Content $content)
    {
        Permission::check('edit-' . $this->permission_name);

        $id = request()->route('id');

        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    public function destroy($id)
    {
        Permission::check('delete-' . $this->permission_name);

        return $this->form()->destroy($id);
    }

    protected function rankGrid(int $rank)
    {
        $grid = new Grid(new ChargeKingReward());
        $grid->disableRowSelector();
        $grid->model()->where('rank', $rank);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->type == 'ware') {
                return @$this->ware->name ?? '';
            } elseif ($this->type == 'vip') {
                return @$this->vip->name ?? '';
            } elseif ($this->type == 'coins') {
                return @$this->target;
            } elseif ($this->type == 'badge') {
                return @$this->badge->name ?? '';
            } elseif ($this->type == 'achievement') {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('expire', __('expire'))->display(function ($expire) {
            return $this->type == 'coins' ? '-' : $expire;
        });
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) use ($rank) {
            $url = url("/admin/charge-king-rewards/create?rank={$rank}");
            $titles = [1 => 'جوائز الملك الأول', 2 => 'جوائز الملك الثاني', 3 => 'جوائز الملك الثالث'];
            $title = $titles[$rank];
            $customButtonHTML = <<<HTML
                <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                    <i class="fa fa-plus"></i> ضيف
                </a>
                <h3 style="margin-right: 10px;">{$title}</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        $grid->disableExport();

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new ChargeKingReward());
        $this->disableFormTools($form);

        $form->hidden('rank')->value(request('rank', 1));

        $form->select('type', trans('type'))->options([
            'ware' => __('ware'),
            'vip' => __('vip'),
            'badge' => __('badge'),
            'coins' => __('coins'),
            'achievement' => __('achievement'),
        ])->when('ware', function () use ($form) {
            $this->addWareField($form);
        })->when('badge', function () use ($form) {
            $this->addBadgeField($form);
        })->when('vip', function () use ($form) {
            $form->select('target2', trans('vips'))->options(function () {
                $ops = [];
                $vips = OVip::query()->select('id', 'name')->get();
                foreach ($vips as $vip) {
                    $ops[$vip->id] = $vip->name;
                }

                return $ops;
            });
        })->when('coins', function () use ($form) {
            $form->number('target3', __('coins'));
        })->when('achievement', function () use ($form) {
            $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));
        });

        $form->number('expire', __('expire'))->required();
        $form->select('gender', __('gender'))->options([
            'all' => __('all'),
            'male' => __('Male'),
            'female' => __('Female'),
        ])->default('all')->required();

        $form->saved(function (Form $form) {
            return redirect(url('admin/charge-king-rewards'));
        });

        return $form;
    }

    protected function addWareField(Form $form)
    {
        $prefix = 'wares';
        $form->belongsTo('target', WaresByType::class, __('Ware'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target')
                ->select('id', __('wares'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Ware::find($id);

                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id'),
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
        $form->hidden('sub_type');
    }

    protected function addBadgeField(Form $form)
    {
        $prefix = 'badges';
        $form->belongsTo('target5', Badges::class, __('Badges'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target5')
                ->select('id', __('badges'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $badge = Badge::find($id);

                    return $badge ? [$badge->id => "{$badge->name}_{$badge->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id'),
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }

    protected function addWareJs()
    {
        \Encore\Admin\Admin::script(<<<'JS'
            function formatWithImage(option) {
                if (!option.id) return option.text;
                let img = option.image
                    ? `<img src="${option.image}" style="width:130px;height:100px;border-radius:4px;margin-right:6px;">`
                    : '';
                return $(`<span>${img}${option.text}</span>`);
            }

            let $select = $('select[data-image-select]');

            $select.select2({
                ajax: {
                    delay: 250,
                    url: $select.data('load-url'),
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    }
                },
                templateResult: formatWithImage,
                templateSelection: formatWithImage,
                escapeMarkup: function (m) { return m; }
            });

            $select.on('select2:select', function (e) {
                let data = e.params.data;
                $('#ware-image-preview').html(
                    data.image
                        ? `<img src="${data.image}" style="max-width:150px;max-height:150px;border:1px solid #ccc;border-radius:4px;">`
                        : ''
                );
            });

            let initialId = $select.val();
            if (initialId) {
                $.getJSON($select.data('load-url'), { id: initialId }, function (data) {
                    if (data && data.length > 0) {
                        let item = data[0];
                        let option = new Option(item.text, item.id, true, true);
                        $select.append(option).trigger('change');
                        if (item.image) {
                            $('#ware-image-preview').html(
                                `<img src="${item.image}" style="max-width:150px;max-height:150px;border:1px solid #ccc;border-radius:4px;">`
                            );
                        }
                    }
                });
            }
        JS);
    }
}