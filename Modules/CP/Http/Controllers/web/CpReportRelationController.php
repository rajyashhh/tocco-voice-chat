<?php

namespace Modules\CP\Http\Controllers\web;

use App\Admin\Actions\CancelCpRelationAction;
use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\CP\Entities\Cp;

class CpReportRelationController extends MainController
{
    public $permission_name = 'cp-report';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('CP Relations & Requests'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    protected function grid()
    {
        $name = request('name') ?: 'relations';


        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }

    protected function relations()
    {
        $grid = new Grid(new Cp());
        $countryID = Common::filterCountryIds();
        $grid->model()->with(
            [
                'cpRelation',
                'level',
                'fromUser',
                'fromUser.profile',
                'fromUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'fromUser.country',
                'fromUser.senderLevel',
                'fromUser.receiverLevel',
                'toUser',
                'toUser.profile',
                'toUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'toUser.country',
                'toUser.senderLevel',
                'toUser.receiverLevel',
            ]
        )->when($countryID, function ($query) use ($countryID) {
            $query->where(function ($q) use ($countryID) {
                $q->whereHas('fromUser', function ($subQuery) use ($countryID) {
                    $subQuery->whereIn('country_id', $countryID);
                })
                    ->orWhereHas('toUser', function ($subQuery) use ($countryID) {
                        $subQuery->whereIn('country_id', $countryID);
                    });
            });
        });

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas('fromUser', function ($query) use ($input) {
                        $query->where('uuid',  $input);
                    })->orWhereHas('toUser', function ($query) use ($input) {
                        $query->where('uuid',  $input);
                    });
                }, __('User'))->placeholder(__('Search by  UUID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('cpRelation.type', __("type"))->select(['friend' => __('friend'), 'bro' => __('bro'), 'lovely' => __('lovely'), 'solution' => __('solution')]);
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', __('status'))->select([0 => __('pending'), 1 => __('active'), 2 => __('accepted'), 3 => __('stope'), 4 => __('restored'), 5 => __('restore'), 6 => __('restore_binding')]);
            });


            $filter->column(1 / 2, function ($filter) {
                $filter->equal('level.level', __("level"));
            });
        });


        $grid->column('fromUsername', __('from user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->fromUser);
        });

        $grid->column('toUsername', __('to user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->toUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column("di", __("coins"));
        $grid->column("level.level", __("level"));
        $grid->column("price", __("price"));
        $grid->column("cpRelation.type", __("relation type"))->display(function ($type) {
            return __($type);
        });
        $grid->column("status", __("status"))
            ->display(function ($status) {
                switch ($status) {
                    case 0:
                        return "<span style='color: blue;'>" . __('Pending') . "</span>";
                    case 1:
                        return "<span style='color: blue;'>" . __('Approved') . "</span>";
                    case 2:
                        return "<span style='color: red;'>" . __('Rejected') . "</span>";
                    case 3:
                        return "<span style='color: red;'>" . __('Relationship Suspended') . "</span>";
                    case 4:
                        return "<span style='color: green;'>" . __('Returned') . "</span>";
                    case 5:
                        return "<span style='color: blue;'>" . __('Awaiting Return') . "</span>";
                    default:
                        return $status;
                }
            })->style('font-weight: bold;');

        if ((\Encore\Admin\Facades\Admin::user()->can('cancel-cp-switch-' . $this->permission_name) || \Encore\Admin\Facades\Admin::user()->can('*'))) {
            $grid->column('actions', __('Actions'))
                ->display(function () {
                    if ($this->status == 1 || $this->status == 4) return (new CancelCpRelationAction())->setCpId($this->id)->render();
                })
                ->style('white-space: nowrap; width: 100px;');
        }
        $grid->disableRowSelector();
        return $grid;
    }

    protected function user()
    {
        $grid = new Grid(new User());
        $grid->model()->whereHas('cps', function ($query) {
            $query->whereIn('status', [1, 4]);
        });

        $grid->column('name', __('name'))
            ->display(function ($name) {
                $uid = @$this->uuid;
                $path = @$this->profile->avatar;
                $url = getImagePath($path);
                $image =  handleShowImageWithTypes($this->id, $url, 40, 40);
                return "$image<br>$name <br>
            <span style=\"color: #aaa; font-size: smaller;\">UID: $uid</span>";
            });
        $grid->column("count", __("count"))
            ->display(function () {
                return Cp::where(function ($q) {
                    $q->where('user_one_id', $this->id)->orWhere('user_two_id', $this->id);
                })->whereIn('status', [1, 4])
                    ->whereHas('cpRelation', function ($q) {
                        $q->where('type', '!=', 'solution');
                    })->count();
                return $this->cps()->whereIn('status', [1, 4])->count();
            });


        return $grid;
    }
}
