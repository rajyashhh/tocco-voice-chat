<?php

namespace App\Admin\Controllers;

use App\Models\Ware;
use Encore\Admin\Grid;
use Modules\Country\Actions\Admin\DedicateSuperAdminRewardAction;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Admin\Controllers\MainController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\HtmlString;
use Encore\Admin\Widgets\Table;
use App\Models\SuperPackageReward;


class SuperAdminRewardController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SuperAdminReward';

    public $permission_name = 'admin-reward';
    public function index(Content $content)
    {

        if (!request()->has('type')) {
            return redirect()->to(url()->current() . '?type=vip');
        }

        session(['last_ware_type' => request()->get('type', 'vip')]);
        return parent::index($content
            ->title(trans('Reward'))
            ->row(function (Row $row) {
                $row->column(12, $this->grid2());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->tabsComponent());
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    private function tabsComponent()
    {
        $content = new Row();

        $types =  ['vip', 'ware', 'badge', 'package'];
        $currentType = request()->get('type', 'vip');

        $box = new Box(content: view('admin.grid.Form.rewardTabs', [
            'types' => $types,
            'currentType' => $currentType
        ]));

        $content->column(12, $box);

        return $content;
    }

    protected function grid2()
    {
        return (new Box(
            title: __('admin.description'),
            content: view('admin.grid.superadmin.description'),
        ));
    }



    protected function grid()
    {
        $type = request('type');

        if ($type == 'vip') {
            $grid = new Grid(new OVip());
            $this->vip($grid);
        } elseif ($type == 'badge') {
            $grid = new Grid(new Badge());
            $this->badge($grid);
        } elseif ($type == 'ware') {
            $grid = new Grid(new Ware());
            $this->ware($grid);
        } elseif ($type == 'package') {
            $grid = new Grid(new SuperPackageReward());
            $this->package($grid);
        } else {
            // Optional: handle invalid type
            $grid = new Grid(new OVip());
        }
        if ($type == 'package') {
            if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
                $grid->column('return', __('dedicate'))->display(function () {

                    return (new \App\Admin\Actions\DedicateAdminPackageReward($this->id))->render();
                });
            }
        } else {
            if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
                $grid->column('return', __('dedicate'))->display(function () {
                    $type = request('type');
                    return (new DedicateSuperAdminRewardAction($this->id, $type))->render();
                });
            }
        }


        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();

        if ((Admin::user()->can($this->permission_name . '-history') || Admin::user()->can('*'))) {
            $grid->tools(function (Grid\Tools $tools) {
                $url = '/admin/admin-rewards-histories?type=' . request('type');
                $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("admin.history") . '</a>';
                $tools->append($button);
            });
        }

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }


    protected function ware($grid)
    {
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($this->input !== null) {
                        $query->where('type', $this->input);
                    }
                }, __('Type'), 'type-ware')->select(getTranslatedWare());
            });
        });

        $grid->model()->whereIn('type', [4, 5, 6, 28]);
        $grid->column('name', __('name'))->sortable();
        $grid->column('show_img', __('show_img'))->image('', 30);
        $grid->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
    }

    protected function package($grid)
    {
        $grid->column('title', __('package'));
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
        $.get('/admin/admin-rewards/' + id, function(html) {
            $('#rewardsModal .modal-body').html(html);
        }).fail(function() {
            $('#rewardsModal .modal-body').html('<p class=\"text-danger\">Failed to load rewards.</p>');
        });
    });
");
    }


    protected function badge($grid)
    {
        $lang = app()->getLocale();
        $grid->model()->whereHas('images', function ($query) use ($lang) {
            $query->where('language', $lang);
        })->with('images')->orderBy('priority', 'desc');
        $grid->column('name', __('name'))->sortable();
        $grid->column('priority', __('Priority'))->sortable();
        $grid->column('images.image', __('image'))->display(function ($path) {
            $path =   $this->images->firstWhere('language', app()->getLocale())?->image;
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
        });
        $grid->filter(function ($filter) {
            $filter->like('name', 'Name');
            $filter->equal('priority', 'Priority');
        });
    }

    protected function vip($grid)
    {
        $grid->column('level', __('level'))->sortable('o_vips.level');
        $grid->column('name', __('name'))->sortable('o_vips.name');
        $grid->column('img', __('img'))->display(function ($path) {
            /** @var OVip $this */
            $defaultImage = asset("images/image.png");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
    }




    public function getRewards($id)
    {
        // Load the RankingRange and its rewards
        $model = SuperPackageReward::with(['packageRewards'])->findOrFail($id);
        $members = $mempers = $model->packageRewards()
            ->with([
                'ware:id,name,img2,show_img',
                'vip:id,name,img',
                'badge:id,name,image',
                "customAchievement",
                'customAchievement.images',
            ])
            ->select('id', 'type', 'target', 'expire', 'quantity', 'super_package_id')
            ->get()
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
                    'quantity' => $memper->quantity,
                    'expire'   => $memper->expire,
                ];
            });

        $table =  new Table(
            ['ID', __('type'), __('gift'), __('image'), __('quantity'), __('expire')],
            $mempers->toArray()
        );

        // Render HTML for modal
        return $table->render();
    }
}
