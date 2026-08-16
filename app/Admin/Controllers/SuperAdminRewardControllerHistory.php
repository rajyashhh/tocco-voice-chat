<?php

namespace App\Admin\Controllers;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Selectables\Badges;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Widgets\Table;
use App\Selectables\SuperAdmins;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Admin\Services\UserService;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\Country\Entities\SuperAdmin;
use Modules\Country\Entities\SuperAdminReward;

class SuperAdminRewardControllerHistory extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SuperAdminReward';

    public $permission_name = 'admin-reward-history';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Reward History'))
            //    ->row(function (Row $row) {
            //         $row->column(12, $this->grid2());
            //     })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    protected function grid2()
    {
        return (new Box(
            title: __('admin.description'),
            content: view('admin.grid.superadmin.description'),
        ));
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
            ->title(trans('Super Admin Reward'))
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
            ->title(trans('Super Admin Reward'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Super Admin Reward'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new SuperAdminReward());
        $type = request('type') ?? 'vip';


        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('superAdmin', function ($subQuery) {
                        $subQuery->where('username', 'like', "%{$this->input}%");
                    })->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', "%{$this->input}%");
                    });
                }, __('username'))->placeholder(__('search for host by username'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('type', __('status'))->select(['ware' => __('ware'), "vip" => __('vip'), 'badge' => __("badge")]);
            });


            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('created_at', $date);
                }, __('Created At'))->date();
            });
        });
        $grid->model()->where('type', $type)
            ->with([
                'superAdmin:id,avatar,username',
                'admin',
                'admin.agency',
                'user',
                'user.senderLevel',
                'user.receiverLevel',
                'user.profile',
                'user.country',
                'areaManager',
                'ware',
                'vip',
                "customAchievement",
                'badge',
                'packageRewards',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ]);
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('id', __('Id'));
        $grid->column('superadmin', __('user'))->display(function ($name) {
            if ($this->user_type == 'user') {

                $user = $this->user;
                if (!$user) {
                    return __('No User');
                }
                return app(UserService::class)->adminUserCard($user);
            }
            $admin = $this->user_type == 'country' ? $this->superAdmin : $this->areaManager;
            $name = @$admin->name ?? '';
            $uid = @$admin->username ?? '';
            $path = @$admin->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this ? url("admin/superadmin-users/{$this->id}") : 0;
            return "<div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>";
        });
        $grid->column('type', __('Type'));
        if ($type != 'package') {
            $grid->column('gift_id', __('gifts'))->display(function () {
                if ($this->type == "ware") {
                    return @$this->ware->name ?? '';
                } elseif ($this->type == "vip") {
                    return @$this->vip->name ?? '';
                } elseif ($this->type == "badge") {
                    return @$this->badge->name ?? '';
                } elseif ($this->type == "coin") {
                    return @$this->target;
                } elseif ($this->type == "achievement") {
                    return $this->customAchievement?->name ?? '';
                }
            });
            if (!request()->filled('_export_')) {
                $grid->column('image', __('image'))->display(function ($path) {
                    if ($this->type == 'ware') {
                        $ware = $this->ware;
                        $path = $ware->img2 ?? ($ware->show_img ?? "");
                    } elseif ($this->type == 'vip') {
                        $vips = $this->vip;
                        $path = $vips->img ?? '';
                    } elseif ($this->type == 'badge') {
                        $path = @$this->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                    } elseif ($this->type == 'achievement') {
                        $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
                    } else {
                        $path = 'coin.png';
                    }

                    /** @var Gift $this */
                    $url = getImagePath($path);
                    return handleShowImageWithTypes($this->id, $url, 50, 50);
                });
            }
            $grid->column('expire', __('Expire'));
            $grid->column('no_reward', __('No reward'));
        } else {

            $grid->column('members', __('Rewards'))->display(function () {
                $text = __('View Rewards'); // Translation key
                return "<button class='btn btn-sm btn-primary show-rewards-modal' data-id='{$this->id}'>$text</button>";
            });

            $modalTitle = __('Rewards'); // PHP variable with translation

            Admin::script("
    $(document).on('click', '.show-rewards-modal', function() {
        var id = $(this).data('id');
        var modalTitle = '" . e($modalTitle) . "'; // escape for JS

        // Show modal
        if (!$('#rewardsModal').length) {
            $('body').append(`
                <div class='modal fade' id='rewardsModal' tabindex='-1'>
                    <div class='modal-dialog modal-lg'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h5 class='modal-title'>${modalTitle}</h5>
                                <button type='button' class='close' data-dismiss='modal'>&times;</button>
                            </div>
                            <div class='modal-body'>Loading...</div>
                        </div>
                    </div>
                </div>
            `);
        } else {
            $('#rewardsModal .modal-title').text(modalTitle);
        }

        $('#rewardsModal .modal-body').html('Loading...');
        $('#rewardsModal').modal('show');

        // Load rewards via AJAX
        $.get('/admin/admin-rewards-histories/' + id, function(html) {
            $('#rewardsModal .modal-body').html(html);
        }).fail(function() {
            $('#rewardsModal .modal-body').html('<p class=\"text-danger\">Failed to load rewards.</p>');
        });
    });
");
        }

        $grid->column('created_at', __('created_at'));
        $grid->column('created_by', __('created by'))->display(function ($name) {

            $admin =  $this->admin;
            $name = @$admin->name ?? '';
            $uid = @$admin->username ?? '';
            $path = @$admin->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this ? url("admin/superadmin-users/{$this->id}") : 0;
            return "<div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>";
        });
        $grid->tools(function (Grid\Tools $tools) {
            $url = url('admin/admin-rewards?type=vip');
            $back = __('back');

            $customButtonHTML = <<<HTML
                     <div style="display: contents; align-items: center;">
                        <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                            <i class="fa fa-arrow-left"></i> {$back}
                        </a>
                    </div>
                HTML;
            $tools->append($customButtonHTML);
        });
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SuperAdminReward());

        $this->addSuperAdminField($form);
        $form->select('type', trans('type'))->options(["ware" => __('ware'), "badge" => __('badge'), "vip" => __('vip'), "coins" => __('coins'), "achievement" => __('achievement')])
            ->when("ware", function () use ($form) {
                $this->addWareField($form);
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as  $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops;
                });
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("coins", function () use ($form) {
                $form->number("target3", __("coins"));
            })->when("achievement", function () use ($form) {
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));

                $form->number('expire', __('expire'))->default(1);
            })->rules('required');

        $form->number('no_reward', __('No reward'))->default(1);


        return $form;
    }

    protected function addWareField(Form $form)
    {
        $prefix = 'wares';
        $form->belongsTo('target1', WaresByType::class, __('Ware'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target1')
                ->select('id', __('wares'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Ware::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }

    protected function addSuperAdminField(Form $form)
    {

        $form->belongsTo('super_admin_id', SuperAdmins::class, __('Super admin'), function ($form) {
            $form->select('id', __('super Admin'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = SuperAdmin::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }

    protected function addBadgeField(Form $form)
    {
        $prefix = 'badges';
        $form->belongsTo('target5', Badges::class, __('Badges'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target5')
                ->select('id', __('badges'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Badge::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }


    public function getRewards($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        // Load the RankingRange and its rewards
        $model = SuperAdminReward::with(['packageRewards'])->findOrFail($id);
        $mempers = $model->packageRewards()->get()
            ->map(function ($memper) {

                $gifts = '';
                $path  = '';

                switch ($memper->type) {
                    case 'ware':
                        $gifts = $memper->ware->name ?? '';
                        $path  = $memper->ware->img2 ?? $memper->ware->show_img ?? '';
                        break;

                    case 'vip':
                        $gifts = $memper->vip->name ?? '';
                        $path  = $memper->vip->img ?? '';
                        break;

                    case 'badge':
                        $gifts = $memper->badge->name ?? '';
                        $path  = $memper->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                        break;

                    case 'coins':
                        $gifts = $memper->target;
                        $path  = 'coin.png';
                        break;

                    case 'achievement':
                        $gifts = $memper->customAchievement?->name ?? '';
                        $path  = $memper->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                        break;
                }

                $defaultImage = asset('images/reward.jpg');

                $url =  getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithSvga(
                    $memper->id,
                    $url,
                    50,
                    50
                );

                return [
                    'id'       => $memper->id,
                    'type'     => $memper->type,
                    'gift'     => $gifts,
                    'image'    => $image,
                    'quantity' => $memper->no_reward,
                    'expire'   => $memper->expire,
                ];
            });

        $table = new Table(
            ['ID', __('type'), __('gift'), __('image'), __('quantity'), __('expire')],
            $mempers->toArray()
        );

        // Render HTML for modal
        return $table->render();
    }
}
