<?php

namespace Modules\Public\Http\Controllers\web;


use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use App\Admin\Services\UserService;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;
use Modules\Public\Entities\WinnerLevelInterval;

class RewardLevelHistoryController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'WinnerLevelInterval';
    public $permission_name = 'room-level-history';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('room level history'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WinnerLevelInterval());

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('name', 'like', "%{$this->input}%");
                    });
                }, __('username'))->placeholder(__('search for host by username'));
            });
            // $filter->column(1 / 2, function ($filter) {
            //     $filter->equal('levelInterval.type', __('type'))->select([
            //         1 => __('receiver'),
            //         2 => __('sender'),
            //         3 => __('room'),

            //     ]);
            // });


            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('created_at', $date);
                }, __('Created At'))->date();
            });
        });

        $grid->model()
            ->selectRaw('
                MIN(id) as id,
                user_id,
                level_interval_id,
                user_level,
                min,
                max,
                 MIN(created_at) as created_at
            ')

            ->with([
                'user',
                'user.senderLevel',
                'user.receiverLevel',
                'user.profile',
                'user.country',
                'levelInterval',
            ])->whereHas('levelInterval', function ($query) {
                $query->where('type', 3);
            })->groupBy('user_id', 'level_interval_id', 'user_level', 'min', 'max');
        $grid->column('id', __('Id'));
        $grid->column('superadmin', __('user'))->display(function ($name) {


            $user = $this->user;
            if (!$user) {
                return __('No User');
            }
            return app(UserService::class)->adminUserCard($user);
        });
           Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('members', __('Rewards'))->display(function () {
            return "<button 
                        class='btn btn-sm btn-primary show-rewards-modal'
                        data-user-id='{$this->user_id}'
                        data-level-interval-id='{$this->level_interval_id}'
                    >
                        " . __('View Rewards') . "
                    </button>";
        });

        $modalTitle = __('Rewards'); // PHP variable with translation

        Admin::script("
            $(document).on('click', '.show-rewards-modal', function() {

                var userId = $(this).data('user-id');
                var levelIntervalId = $(this).data('level-interval-id');
                var modalTitle = '" . e(__('Rewards')) . "';

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
                }

                $('#rewardsModal .modal-body').html('Loading...');
                $('#rewardsModal').modal('show');

                $.get(
                    '/admin/winner-level-intervals-rewards/' + userId + '/' + levelIntervalId,
                    function (html) {
                        $('#rewardsModal .modal-body').html(html);
                    }
                ).fail(function () {
                    $('#rewardsModal .modal-body')
                        .html('<p class=\"text-danger\">Failed to load rewards.</p>');
                });
            });
        ");

        $grid->column('user_level', __('level'));
        // $grid->column('type', __('type'))->display(function ($value) {
        //     return  $this->levelInterval->type == 3 ? __("room") : ($this->levelInterval->type == 1 ? __("receiver") : __("sender"));
        // });
        $grid->column('created_at', __('Created at'));
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }







    public function getRewards($user_id, $level_interval_id)
    {
        $models = WinnerLevelInterval::with([
            'rewardLevelInterval.ware',
            'rewardLevelInterval.vip',
            'rewardLevelInterval.customAchievement',
            'rewardLevelInterval.customAchievement.images',
        ])
            ->where('user_id', $user_id)
            ->where('level_interval_id', $level_interval_id)
            ->get();

        $members = $models->map(function ($member) {

            $gift = '';
            $path = '';

            switch ($member->type) {

                case 'ware':
                    $gift = $member->rewardLevelInterval?->ware?->name ?? '';
                    $path = $member->rewardLevelInterval?->ware?->img2
                        ?? $member->rewardLevelInterval?->ware?->show_img
                        ?? '';
                    break;

                case 'vip':
                    $gift = $member->rewardLevelInterval?->vip?->name ?? '';
                    $path = $member->rewardLevelInterval?->vip?->img ?? '';
                    break;

                case 'coins':
                    $gift = $member->rewardLevelInterval?->target;
                    $path = 'coin.png';
                    break;

                case 'achievement':
                    $gift = $member->rewardLevelInterval?->customAchievement?->name ?? '';
                    $path = $member->rewardLevelInterval?->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                    break;
            }

            $defaultImage = asset('images/reward.jpg');
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithSvga(
                $member->id,
                $url,
                50,
                50
            );

            return [
                'id'     => $member->id,
                'type'   => $member->type,
                'gift'   => $gift,
                'image'  => $image,
                'expire' => $member->rewardLevelInterval?->expire,
            ];
        });

        $table = new Table(
            ['ID', __('type'), __('gift'), __('image'), __('expire')],
            $members->toArray()
        );

        return $table->render();
    }
}
