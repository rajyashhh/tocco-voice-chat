<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Emoji;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Encore\Admin\Widgets\Table as WidgetsTable;
use Modules\SalaryTransaction\Actions\AccepRequestAction;
use Modules\SalaryTransaction\Actions\CancelRequestAction;

class RequestProblemController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'transaction-request-problem';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('transaction request problem'))
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
            ->title(trans('transaction request problem'))
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
            ->title(trans('transaction request problem'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('transaction request problem'))
            ->body($this->form()));
    }
    protected function grid()
    {
        $grid = new Grid(new AdminCheck());
        $countryID = Common::filterCountryIds();

        $grid->model([
            'request',
            'request.agency.owner',
            'request.agency.owner.profile',
            'request.agency.owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'request.host',
            'request.host.profile',
            'request.host.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')

        ])
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('request.host', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('request.agency', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->where("admin_check", '!=', 1);
        $grid->column('id', __('Id'));
        $grid->column('request_id', __('requests'))->modal('request info', function ($model) {
            $show = new Show($model);
            $show->id('ID');
            $show->field('request.agency_id', __('agency id'));
            $show->field('request.agency_owner_id', __('agency owner id'));
            $show->field('request.host_id', __('host id'));
            $show->field('request.status', __('status'));
            $show->field('request.usd', __('usd'));
            $show->field('request.coins', __('coins'));
            $show->field('request.host_check', __('host_check'))->display(function ($q) {
                if ($q->host_check == 1) {
                    return __('Accept');
                } elseif ($q->host_check == 2) {
                    return __('Reject');
                } else {
                    return __('Pending');
                }
            });
            $show->field('request.bill_image', __('bill image'))->image();
            return $show;
        });
        $grid->column(__('bill image'))->modal('show image', function ($model) {
            $img = $model->bill_image;
            if ($img == null || $img == '') {
                return 'No image founded';
            }

            $img = getDriverUrl() . '/' . $img;
            $img = "<img src='" . $img . "' style='width:500px;height:500px' class='img img-thumbnail'$ />";

            return (new WidgetsTable([''], [[$img]]));
        });

        $grid->column(__('Shipping Agent Name'))
            ->display(function () {
                $name =  $this->request?->agency?->owner?->name ?? '';
                $uid = @$this->request?->agency?->owner->uuid;
                $path = @$this->request?->agency?->owner?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                $showUrl = url("admin/users/{$this->request?->agency?->owner->id}");
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </div>
            ";
            });
        $grid->column(__('Host Name'))->display(function () {
            $name = $this->request?->host?->name ?? '';
            $uid = @$this->request?->host->uuid;
            $path = @$this->request?->host?->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/users/{$this->request?->host->id}");
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </div>
            ";
        });

        $grid->column('type', __('type'));
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
            $actions->add(new AccepRequestAction());
            $actions->add(new CancelRequestAction());
        });

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
        $show = new Show(Emoji::findOrFail($id));

        $show->id(__('admin.ID'));
        $show->pid('pid');
        $show->name('name');
        $show->emoji('emoji');
        $show->t_length('t_length');
        $show->enable('enable');
        $show->sort('sort');
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Emoji);
        $this->disableFormTools($form);

        $form->display(__('admin.ID'));
        $form->select('pid', __('pid'))->options(function () {
            $ops = [0 => 'root'];
            $ps = Emoji::query()->where('enable', 1)->where('pid', 0)->where('id', '!=', $this->id)->get();
            foreach ($ps as $p) {
                $ops[$p->id] = $p->name;
            }
            return $ops;
        });
        $form->text('name', __('name'));
        $form->file('emoji', __('emoji'));
        $form->number('t_length', __('t_length'));
        $form->switch('enable', __('enable'))->states(Common::getSwitchStates());
        $form->number('sort', __('sort'));

        return $form;
    }
}
