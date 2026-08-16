<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Widgets\Table as WidgetsTable;
use Encore\Admin\Facades\Admin;
use Modules\SalaryTransaction\Entities\SalaryRequest;

class SalaryRequestController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */

    public $permission_name = 'salary-requests';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('salary-requests'))
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
            ->title(trans('salary-requests'))
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
            ->title(trans('salary-requests'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('salary-requests'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SalaryRequest());
        $countryID = Common::filterCountryIds();

        $grid->model()->with([
            'agency',
            'agencyOwner',
            'agencyOwner.profile',
            'agencyOwner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'host',
            'host.profile',
            'host.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'payment_gateway',
            'country'


        ])
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('agencyOwner', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('host', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->orderByDesc('id');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', __('status'))->select([0 => __('waiting'), 1 => __('accepting'), 2 => __('transferred'), 3 => __('completed'), 4 => __('rejected')]);
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency_id', __('agency'))->select(Common::by_agency_filter());

                $filter->equal('agency_owner_id', __('Agency owner'))->select(Common::by_user_filter());
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('agency.name', __('Agency'))
            ->display(function ($name) {
                $path = @$this->agency->img;
                $defaultImage = asset("images/icon-agency.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                if ($this->agency) {
                    $showUrl = url("admin/agencies/{$this->agency->id}");
                    $link = "
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                    </a>
                ";
                } else {
                    $link = "<span style='color: gray;'>No Agency</span>"; // Handle missing agency
                }

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    $link
                </div>
            ";
            });

        $grid->column('agencyOwner.name', __('Agency owner'))
            ->display(function ($name) {
                $name = $name ?? '';
                $uid = @$this->agencyOwner->uuid;
                $path = @$this->agencyOwner?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                $showUrl = url("admin/users/{$this->agencyOwner->id}");
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

        $grid->column('host.name', __('host'))
            ->display(function ($name) {
                $uid = @$this->host->uuid;
                $path = @$this->host?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                $showUrl = url("admin/users/{$this->host->id}");
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
        $grid->column('status', __('status'))->display(function ($status) {
            switch ($status) {
                case 0:
                    return "<span style='color: orange; font-weight: bold;'>" . __('waiting') . "</span>";
                case 1:
                    return "<span style='color: blue; font-weight: bold;'>" . __('accepting') . "</span>";
                case 2:
                    return "<span style='color: purple; font-weight: bold;'>" . __('transferred') . "</span>";
                case 3:
                    return "<span style='color: green; font-weight: bold;'>" . __('completed') . "</span>";
                case 4:
                    $text = $this->request_admin_status == 1 ? __('rejectedAdmin') : __('rejected');
                    return "<span style='color: red; font-weight: bold;'>$text</span>";
            }
        });
        $grid->column('payment_gateway.title', __('Payment gateway'));
        $grid->column('country.name', __('country'));
        $grid->column('usd', __('Usd'))->display(function ($usd) {
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$usd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        $grid->column('coins', __('Coins'))->display(function ($coins) {
            $image = asset('images/coin.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center;'>

                        <span>{$coins}</span>
                         <img src='{$image}' alt='Coins' width='20' height='20'>
                    </div>";
        });
        // $grid->column('bill_image', __('bill image'))->display(function ($img) {
        //     $defaultImage = asset("images/background_room.jpg");
        //     $path = getImagePath($img) ??$defaultImage ;
        //     if (!isImageExists($path)) {
        //         $path = $defaultImage;
        //     }
        //     $parsedUrl = parse_url($path);
        //     $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");

        //     return "
        //             <img src='$correctUrl' style='width: 80px; height: 80px; border-radius: 5px; cursor: pointer;' onclick='openModal(\"$correctUrl\")' />

        //             <div id='imageModal' class='modal' style='display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); text-align:center;'>
        //                 <span onclick='closeModal()' style='position:absolute; top:10px; right:20px; font-size:30px; color:white; cursor:pointer;'>&times;</span>
        //                 <img id='modalImage' style='display:block; margin:auto; max-width:90%; max-height:90%; margin-top:50px; border-radius:5px;' />
        //             </div>

        //             <script>
        //                 function openModal(src) {
        //                     let modal = document.getElementById('imageModal');
        //                     let modalImage = document.getElementById('modalImage');
        //                     modal.style.display = 'block';
        //                     modalImage.src = src;
        //                 }

        //                 function closeModal() {
        //                     document.getElementById('imageModal').style.display = 'none';
        //                 }

        //                 // Close modal when clicking outside the image
        //                 document.getElementById('imageModal').addEventListener('click', function(event) {
        //                     if (event.target === this) {
        //                         closeModal();
        //                     }
        //                 });
        //             </script>
        //         ";
        // });

        // Admin::script("
        // if (window.innerWidth >= 1024) { // Example threshold for desktop screens
        //     $('.table-responsive').removeClass('table-responsive');
        //     }
        // ");

        $grid->column(__('bill image'))->modal('show image', function () {
            $img = $this->bill_image;
            if ($img == null || $img == '') {
                return 'No image founded';
            }

            $img = getDriverUrl() . '/' . $img;
            $img = "<img src='" . $img . "' style='width:500px;height:500px' class='img img-thumbnail'$ />";

            return (new WidgetsTable([__('img')], [[$img]]));
        });

        $grid->disableActions();
        $grid->disableCreateButton();
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
        $show = new Show(SalaryRequest::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('agency_id', __('Agency id'));
        $show->field('host_id', __('Host id'));
        $show->field('status', __('Status'));
        $show->field('payment_gateway_id', __('Payment gateway id'));
        $show->field('country_id', __('Country id'));
        $show->field('agency_owner_id', __('Agency owner id'));
        $show->field('usd', __('Usd'));
        $show->field('coins', __('Coins'));
        $show->field('bill_image', __('Bill image'));
        $show->field('host_check', __('Host check'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('note', __('Note'));
        $show->field('request_admin_status', __('Request admin status'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SalaryRequest());

        $form->number('agency_id', __('Agency id'));
        $form->number('host_id', __('Host id'));
        $form->number('status', __('Status'));
        $form->number('payment_gateway_id', __('Payment gateway id'));
        $form->number('country_id', __('Country id'));
        $form->number('agency_owner_id', __('Agency owner id'));
        $form->number('usd', __('Usd'));
        $form->number('coins', __('Coins'));
        $form->text('bill_image', __('Bill image'));
        $form->number('host_check', __('Host check'));
        $form->text('note', __('Note'));
        $form->switch('request_admin_status', __('Request admin status'));

        return $form;
    }
}
