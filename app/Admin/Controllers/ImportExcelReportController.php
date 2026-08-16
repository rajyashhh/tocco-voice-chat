<?php
namespace App\Admin\Controllers;
use App\Admin\Extensions\AgencyExporter;
use App\Admin\Extensions\UserExporter;
use App\Helpers\Common;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\User;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Request;
use Encore\Admin\Form;
use App\Imports\FirstImport;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\DB;

class ImportExcelReportController extends MainController {

    public function index ( Content $content )
    {
        return $content
            ->title("Reports")
            ->description("Charges")
            ->row(function($row) {
                $row->column(12, $this->grid());
            });
    }

    protected function grid(){
        $name= "result";
        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableColumnSelector ();

        return $grid;
    }

    protected function result()
    {
        $grid = new Grid(new CoinLog());

        $grid->filter (function (Grid\Filter $filter){
            $filter->expand ();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $uuid = Request::input('uuid');
                        $query->whereHas('sender', function ($q) use ($uuid) {
                            $q->where('uuid',$uuid);
                        });

                }, __('uuid'));
            });
            $filter->where(function ($query) {
                $name = Request::input('charger_name');
                    $query->whereHas('sender', function ($q) use ($name) {
                        $q->where('name', 'like', "%{$name}%");
                    });

            }, __('charger'));

        });
        $grid->model ();
        $grid->column ('id',__ ('id'));
        $grid->column ('user_id',__ ('charger'))->display (function (){
            if (!isset($this->user->name)) {
                return "not found user";
            }
            return @$this->user->name ."<br>"."#".@$this->user->uuid;
        });
        $grid->column ('obtained_coins',__ ('amount'));
        $grid->column ('trx',__ ('trx'));
        $grid->column ('status',__ ('status'))->display (function (){
            if ($this->status == 1) {
                return "success";
            }elseif ($this->status == 0) {
                return "faild";
            }
        });
        return $grid;
    }

    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    protected function form()
    {
        $form = new Form(new User);

        $form->file('file', 'file');

        return $form;
    }

    public function store()
    {
        try {
            Excel::import(new FirstImport, request()->file('file'));
            admin_success(trans('admin.succeeded'), trans('admin.update_succeeded'));
            return back();
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Excel import failed: '.$th->getMessage(), ['exception' => $th]);
            admin_error(trans('admin.failed'), $th->getMessage());
            return back()->withErrors([$th->getMessage()]);
        }

    }
}
