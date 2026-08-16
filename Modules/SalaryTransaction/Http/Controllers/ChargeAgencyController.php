<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\ShippingAgency;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Cache;
use Modules\SalaryTransaction\Entities\ChargeAgency as EntitiesChargeAgency;

class ChargeAgencyController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'charge-agency';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Verified Charging Agents'))
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
            ->title(trans('agency-country'))
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
            ->title(trans('agency-country'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('agency-country'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new EntitiesChargeAgency());
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->with([
                'agency',
                'agency.owner.profile',
                'agency.owner.country',
                'agency.owner.senderLevel',
                'agency.owner.receiverLevel',
                'agency.owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])
            ->when($countryID, fn($q) => $q->whereHas('agency', fn($q) => $q->whereIn('country_id', $countryID)))
            ->whereHas('agency');

        $grid->id(__('ID'));
        $grid->column('agency.name', __('Agency'))->display(function ($name) {
            if (!$this->agency) {
                return;
            }
            $path = @$this->agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            $image = "<img src='{$url}' onerror=\"this.onerror=null;this.src='{$defaultImage}'\" style='height:40px !important; width:40px !important; border-radius:0%; object-fit:cover;' alt='' />";

            $profileUrl = route('admin.agency.profile', ['id' => $this->agency->id]);

            return "<a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            {$image}
                            <div style='display: flex; flex-direction: column;'>
                                <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>
                                <span style='font-size: smaller;'>ID: {$this->agency->id}</span>
                            </div>
                        </div>
                    </a>";
        });


        $grid->column('name', __('owner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->agency->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('agency.phone', trans('phone'));
        $this->extendGrid($grid);

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(EntitiesChargeAgency::findOrFail($id));

        $show->id(__('admin.ID'));
        $show->name('name');
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
        $form = new Form(new EntitiesChargeAgency);
        $this->disableFormTools($form);

        $form->display(__('admin.ID'));
        $form->select('agency_id', __('agency'))->options(function () {
            $ops = [0 => 'root'];
            // $ps = Agency::query ()->WhereDoesntHave('chargeAgency')->where("Shipping_agency",1)->get ();
            $ps = ShippingAgency::query()->get();
            foreach ($ps as $p) {
                $ops[$p->id] = $p->name;
            }
            return $ops;
        })->rules(function ($form) {
            if (!$id = $form->model()->id) {
                return 'unique:charge_agencies,agency_id';
            }
        });
        return $form;
    }
}
