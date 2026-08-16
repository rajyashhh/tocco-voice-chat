<?php

namespace Modules\Region\Http\Controllers;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use App\Models\Country;
use App\Selectables\Bds;
use App\Enums\PermissionType;
use App\Selectables\Agencies;
use App\Selectables\Families;
use App\Models\OfficialMessage;
use App\Jobs\OfficialMessageJob;
use Encore\Admin\Layout\Content;
use App\Models\OfficialMessageAdmin;
use Illuminate\Support\Facades\Auth;
use App\Selectables\ShippingAgencies;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class OfficialMessageController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'official-messages';



    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Official messages'))
            ->body($this->grid()));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('official-messages'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new OfficialMessage);
        $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;
        $grid->model()->where('admin_id', $authId)->where('type', 2)->orderByDesc('id');

        $grid->id(__('ID'));

        $grid->title(trans('title'));


        $grid->content(__('content'));
        $grid->feature(__('feature'));
        $grid->column('img', trans('img'))->display(function ($img) {
            $defaultImage = asset("images/background_room.jpg");
            $path = getImagePath($img);
            if (!isImageExists(@$path)) {
                $path = $defaultImage;
            }
            $parsedUrl = parse_url($path);
            $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");

            return "
                    <img src='$correctUrl' style='width: 50px; height: 50px; border-radius: 5px; cursor: pointer;' onclick='openModal(\"$correctUrl\")' />

                    <div id='imageModal' class='modal' style='display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); text-align:center;'>
                        <span onclick='closeModal()' style='position:absolute; top:10px; right:20px; font-size:30px; color:white; cursor:pointer;'>&times;</span>
                        <img id='modalImage' style='display:block; margin:auto; max-width:90%; max-height:90%; margin-top:50px; border-radius:5px;' />
                    </div>

                    <script>
                        function openModal(src) {
                            let modal = document.getElementById('imageModal');
                            let modalImage = document.getElementById('modalImage');
                            modal.style.display = 'block';
                            modalImage.src = src;
                        }

                        function closeModal() {
                            document.getElementById('imageModal').style.display = 'none';
                        }

                        // Close modal when clicking outside the image
                        document.getElementById('imageModal').addEventListener('click', function(event) {
                            if (event.target === this) {
                                closeModal();
                            }
                        });
                    </script>
                ";
        });
        $grid->column('url', trans('url'))
            ->display(function ($value) {
                return "<span style='color: #89CFF0;'>$value</span>";
            });
        $grid->created_at(trans('admin.created_at'));
        $grid->disableExport();

        $this->extendGrid($grid);
        $grid->actions(function ($actions) {
            $actions->disableEdit();
        });

        return $grid;
    }


    protected function form()
    {
        $form = new Form(new OfficialMessageAdmin);
        $this->disableFormTools($form);
        $form->display('ID');
        $form->text('title', __('title'))->rules('required|max:255');
        $form->textarea('content', __('content'))->rules('required');
        $form->image('img', __('img'));
        $form->text('url', __('url'));

        $form->hidden('admin_type', __('type'))->default(PermissionType::AREA_MANAGER->value);
        $form->hidden('type', __('type'))->default(2);
        $this->selectFeature($form);
        $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;
        $form->hidden('admin_id', __('type'))->default($authId);

        return $form;
    }


    protected function selectFeature(Form $form)
    {
        $countriesIds = Common::areaCountriesV2(Auth::id());
        $countries = Country::selectRaw('concat(name, " - ", e_name) as name, id')->when(!empty($countriesIds), function ($q) use ($countriesIds) {
            $q->whereIn('id', $countriesIds);
        })
            ->pluck('name', 'id')
            ->toArray();
        $form->select('feature', trans('feature'))->options([
            'agency'   => __('agency'),
            'family' => __('family'),
            'users'   => __('users'),
            'bds'  => __('BDs'),
            'shipping_agency'  => __('shipping agency')
        ])->when('agency', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('type'))->options([
                'area_country' => __('All Agencies around your region'),
                'country' => __('All Agencies in Country'),
                'ids'   => __('Specific Agency by ID'),
            ])->when('ids', function (Form $form) {
                $form->belongsToMany('agency_ids', Agencies::class, trans('agencies'));
                $form->select('member_title', trans('member'))->options([
                    'owner'   => __('owner'),
                    'admin' => __('admins'),
                    'members'   => __('members'),
                ])->default('owner');
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('family', function (Form $form) {
            $form->belongsToMany('feature_ids', Families::class, trans('families'));
            $form->select('member_title', trans('member'))->options([
                'owner'   => __('owner'),
                'admin' => __('admins'),
                'members'   => __('members'),
            ])->default('owner');
        })->when('users', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('type'))->options([
                'area_country' => __('Users around your regions'),
                'country' => __('Users in Specific Country'),
                'logout'   => __('Logged Out Users'),
            ])->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('bds', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('type'))->options([
                'area_country' => __('bds around your regions'),
                'ids'   => __('bds'),
                'country' => __('Bds in Specific Country'),

            ])->when('ids', function (Form $form) {
                $form->belongsToMany('Bds_id', Bds::class, trans('Bds'));
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('country'))
                    ->options($countries);
                //->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('shipping_agency', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('type'))->options([
                'ids'   => __('Specific shipping Agency by ID'),
                'area_country' => __('shipping agency around your regions'),
                'country' => __('Shipping Agencies in Specific Country'),
            ])->when('ids', function (Form $form) use ($countries) {
                $form->belongsToMany('shipping_agency_ids', ShippingAgencies::class, trans('agencies'));
                $form->select('member_title', trans('member'))->options([
                    'owner'   => __('owner'),
                ])->default('owner');
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        });


        $form->saved(function (Form $form) {
            $model = $form->model();
            $data = request()->except(['img']);
            dispatch(new OfficialMessageJob($model, $data, Auth::user()))->onQueue('official-message');
        });
    }
}
