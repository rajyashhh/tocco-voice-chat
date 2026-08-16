<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Models\Agency;
use App\Models\BdAgencyHostSallary;
use App\Models\Charge;
use App\Models\BdSalary;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Layout\Row;
use Encore\Admin\Controllers\AdminController;



class BdSalariesController extends AdminController
{
    use HasResourceActions;

    protected $title = "Charges";

    public $permission_name = "browse-get-salary-bd";

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $appID = Auth::user()->id;

        $netSalary = BdSalary::where('bd_id', $appID)
        ->selectRaw('SUM(salary) as total_sallary, SUM(cut_amount) as total_cut')
        ->first();
        $totalCut =$netSalary->total_cut;
        $total_sallary =$netSalary->total_sallary;
        $finalSalary = ($netSalary->total_sallary ?? 0) - ($netSalary->total_cut ?? 0);
        $finalSalary = truncateAndTrim($finalSalary,2);
            return $content
                ->header(trans('salaries'))
                ->description(trans('salaries'))

        ->row(function ($row) use ($finalSalary) {
            $row->column(12, view('admin.grid.bd.wallet', ['finalSalary' => $finalSalary]));
        })
        ->row(function (Row $row) use ($total_sallary, $totalCut ) {
            $row->column(6, new InfoBox(__('total_sallary'), 'money', 'green', '', truncateAndTrim($total_sallary ,2) . ' 💰' ));
            $row->column(6, new InfoBox(__('totalCut'), 'money', 'red', 'charges', truncateAndTrim($totalCut,2)));
        })

        ->row(function ($row) {
            $row->column(12, $this->grid());
        });
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {


    $grid = new Grid(new BdAgencyHostSallary());
    $appID = Auth::user()->id;



    $grid->model()
        ->where('bd_id', $appID)
        ->with('agency')
        ->selectRaw('
            agency_id,
            month,
            year,
            SUM(CAST(amount AS DECIMAL(15,4))) as total_bd_sallary,
            COUNT(*) as count,
            (
                SELECT SUM(CAST(sallary AS DECIMAL(15,4)))
                FROM user_sallaries
                WHERE user_agency_id = bd_agency_host_sallaries.agency_id
                    AND month = bd_agency_host_sallaries.month
                    AND year = bd_agency_host_sallaries.year
            ) as total_user_sallary,
            (
                SELECT SUM(CAST(agency_sallary AS DECIMAL(15,4)))
                FROM user_sallaries
                WHERE user_agency_id = bd_agency_host_sallaries.agency_id
                    AND month = bd_agency_host_sallaries.month
                    AND year = bd_agency_host_sallaries.year
            ) as total_agency_sallary
        ')
        ->groupBy('agency_id', 'month', 'year')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc');


        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
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

            $currentYear = now()->year;
            $years = [];
            for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                $years[$i] = $i;
            }
            $filter->equal('year', __('Year'))->select($years);

            $filter->equal('agency_id', __('Agency'))->select(
                Agency::where('bd_id',Auth::id())->pluck('name', 'id')->toArray()
            );
        });

        $grid->column('agency.name', trans('agency'))->display(function () {
            $agency = $this->agency;
            if (request()->filled('_export_')) {
                return $agency?->name;
            }
            if (!$agency) {
                return "<span style='color:red;'>No agency</span>";
            }

            $cacheKey = "agency_image_{$agency->id}";
            $image = \Cache::remember($cacheKey, 3600, function () use ($agency) {
                $path = @$agency->img;
                $defaultImage = asset("images/icon-agency.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                return handleShowImageWithTypes($agency->id, $url, 40, 40);
            });

            $profileUrl = route('bd.agency.profile', ['id' => $agency->id]);

            return "
                <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        {$image}
                        <div style='display: flex; flex-direction: column;'>
                            <span style='text-decoration: underline; cursor: pointer;'>{$agency->name}</span>
                            <span style='font-size: smaller;'>ID: {$agency->id}</span>
                        </div>
                    </div>
                </a>
            ";
        });

        $grid->column('total_bd_sallary', trans('totalBd'))->display(function ($value) {

            return truncateAndTrim($value , 2);
        });

        $grid->column('total_user_sallary', __('Total Users Sallary'))->display(function ($value) {

            return truncateAndTrim($value, 2);
        });


        $grid->column('total_agency_sallary', __('Total Agency Sallary'))->display(function ($value) {
            return truncateAndTrim($value , 2);
        });
        $grid->disableRowSelector();

        // $grid->column('total_diamond', __('Total Diamond'))->display(function ($value) {
        //     return number_format($value, 2);
        // });
        $grid->column('month', __('month'));
        $grid->column('year', __('year'));
        // $grid->tools(function (Grid\Tools $tools) {
        //     $url = 'charges';
        //     $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("Charge History") . '</a>';
        //     $tools->append($button);
        // });
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
        $show = new Show(Charge::find($id));


        $this->extendShow ($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Charge);



        return $form;
    }
}
