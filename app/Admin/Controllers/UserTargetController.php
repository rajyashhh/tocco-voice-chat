<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\UserTarget;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UserTargetController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'hosts-target';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Hosts Target'))
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
            ->title(trans('users targets'))
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
            ->title(trans('users targets'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return  parent::create($content
            ->title(trans('users targets'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new UserSallary);
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->with([
                'user',
                'user.profile',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'agency'

            ])
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID));
            }));
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->disableIdFilter();

            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($subQuery) {
                        $subQuery->where('uuid', 'like', "%{$this->input}%");
                    });
                }, __('UUID'))->placeholder(__('search for host by UUID'));
            });

            $filter->column('1/2', function ($filter) {
                $filter->equal('user_agency_id', __('agency'))->select(Common::by_agency_filter());
            });

            $filter->column('1/2', function ($filter) {
                $filter->equal('month', __('Month'))->select([
                    1 => __('January'),
                    2 => __('February'),
                    3 => __('March'),
                    4 => __('April'),
                    5 => __('May'),
                    6 => __('June'),
                    7 => __('July'),
                    8 => __('August'),
                    9 => __('September'),
                    10 => __('October'),
                    11 => __('November'),
                    12 => __('December'),
                ]);
            });

            $filter->column('1/2', function ($filter) {
                $currentYear = now()->year;
                $years = [];
                for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                    $years[$i] = $i;
                }
                $filter->equal('year', __('Year'))->select($years);
            });
        });
        $grid->column('id', __('id'));
       
        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('user_agency_id', __('agency'))->display(function () {
            $name = @$this->agency->name ?? '';
            if (request()->filled('_export_')) {
                return $name;
            }
            $path = @$this->agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this->agency ? url("admin/agencies/profile/{$this->agency->id}") : '#';

            return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                $image
                <span>$name</span>
            </div>
        ";
        });
        $grid->column('month', __('date'))->display(function ($month) {
            return $month . '/' . $this->year;
        });

        $grid->column('hours', __('hours'))->display(function ($hours) {
            return $hours;
            //            return explode('/', $hours)[1] ?? 0;
        });
        $grid->column('days', __('days'))->display(function ($days) {
            return $days;
            //            return explode('/', $days)[1] ?? 0;
        });

        //        $grid->column('target_diamonds',__ ('target diamonds'))->display(function ($diamond) {
        //            return explode('/', $diamond)[1] ?? 0;
        //        });

        //        $grid->column('hours', __('user hours'))->display(function ($hours) {
        //            return explode('/', $hours)[0] ?? 0;
        //        });
        //        $grid->column('days', __('user days'))->display(function ($days) {
        //            return explode('/', $days)[0] ?? 0;
        //        });
        $grid->column('diamond', __('user diamonds'))->display(function ($diamond) {
            if (request()->filled('_export_')) {
                return $diamond;
            }
            //            $usd = explode('/', $diamond)[0] ?? 0;
            $image = asset('images/diamond.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$diamond}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        $grid->column('sallary', __('salary'))->display(function ($usd) {
            if (request()->filled('_export_')) {
                return $usd;
            }
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$usd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('cut_amount', __('cut amount'))->display(function ($usd) {
            if (request()->filled('_export_')) {
                return $usd;
            }
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            $formattedUsd = number_format((float)$usd, 2);
            return "<div style='display: flex; align-items: center; '>

                        <span>{$formattedUsd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('Net Salary', __('User Net Salary'))->display(function ($usd) {
            $usd = $this->sallary - $this->cut_amount;
            if (request()->filled('_export_')) {
                return $usd;
            }
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$usd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('agency_sallary', __('agency obtain'))->display(function ($usd) {
            if (request()->filled('_export_')) {
                return $usd;
            }
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            $formattedUsd = number_format((float)$usd, 2);
            return "<div style='display: flex; align-items: center; '>

                        <span>{$formattedUsd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->disableActions();
        $grid->disableCreateButton();
        $this->extendGrid($grid);
        return $grid;
    }
}
