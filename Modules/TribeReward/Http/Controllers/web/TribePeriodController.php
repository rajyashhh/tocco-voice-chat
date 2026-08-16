<?php

namespace Modules\TribeReward\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\TribeReward\Entities\TribePeriod;

class TribePeriodController extends MainController
{
    public $permission_name = 'tribe-periods';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Tribe Periods'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Tribe Periods'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__('Tribe Periods'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Tribe Periods'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new TribePeriod());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('start_date', __('Start Date'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $grid->column('end_date', __('End Date'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        if (Admin::user()->can('browse-tribe-tops') || Admin::user()->can('*')) {
            $grid->column(__('Procedures'))->display(function () {
                $url = url('admin/tribe_tops/'.$this->id);
                $gifts = __('Tribe Tops');
                return "<a href='{$url}' class='btn btn-sm btn-info'>{$gifts}</a>";
            });
        }

        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(TribePeriod::findOrFail($id));

        $show->field('id', __('ID'));
        $show->column('start_date', __('Start Date'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $show->column('end_date', __('End Date'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new TribePeriod());

        $form->date('start_date', __('Start Date'))->rules('required|date|after_or_equal:today');
        $form->date('end_date', __('End Date'))->required()->readonly();

        $form->html(<<<HTML
            <script>
            function arabicToEnglish(str) {
                if(!str) return str;
                var arNums = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
                for(var i=0; i<10; i++) {
                    str = str.replace(new RegExp(arNums[i], 'g'), i);
                }
                return str;
            }

            $(function(){
                $('#start_date').on('change blur', function(){
                    var start = $(this).val();

                    if(start){
                        var enStart = arabicToEnglish(start);
                        if (enStart !== start) $(this).val(enStart);
                        var dateRegex = /^(\d{4})-(\d{2})-(\d{2})/;
                        var match = enStart.match(dateRegex);

                        if(match){
                            var formatted = match[1] + '-' + match[2] + '-' + match[3];

                            var selected = new Date(formatted);
                            if (!isNaN(selected)) {
                                selected.setDate(selected.getDate() + 15);
                                var year = selected.getFullYear();
                                var month = ("0" + (selected.getMonth() + 1)).slice(-2);
                                var day = ("0" + selected.getDate()).slice(-2);
                                var newVal = year + "-" + month + "-" + day;
                                $('#end_date').val(newVal).trigger('change');
                            } else {
                                console.log("Could not parse date after formatting!");
                            }
                        } else {
                            console.log("Could not match date in:", enStart);
                        }
                    }
                });

                $('#start_date, #end_date').on('change blur', function(){
                    var val = $(this).val();
                    var enVal = arabicToEnglish(val);
                    if (val !== enVal) $(this).val(enVal);
                });
            });
            </script>
            HTML);

        $form->saving(function (Form $form) {
            $form->start_date = convertArabicToEnglishNumbers($form->start_date);
            $form->end_date = convertArabicToEnglishNumbers($form->end_date);

            $query = TribePeriod::where(function($q) use ($form) {
                $q->where(function($q2) use ($form) {
                    $q2->where('start_date', '<=', $form->end_date)
                        ->where('end_date', '>=', $form->start_date);
                });
            });

            if ($form->model()->id) {
                $query->where('id', '!=', $form->model()->id);
            }

            if ($query->exists()) {
                $error = __('There is already an event running these dates!');
                admin_error($error);
                return back()->withInput();
            }
        });

        return $form;
    }
}
