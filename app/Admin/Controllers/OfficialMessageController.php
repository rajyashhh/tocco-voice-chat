<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Country;
use App\Selectables\Bds;
use Illuminate\Http\Request;
use App\Selectables\Agencies;
use App\Selectables\Families;
use Encore\Admin\Facades\Admin;
use App\Jobs\OfficialMessageJob;
use Encore\Admin\Layout\Content;
use App\Models\OfficialMessageAdmin;
use Illuminate\Support\Facades\Auth;
use App\Selectables\ShippingAgencies;
use Encore\Admin\Controllers\HasResourceActions;
use App\Models\OfficialMessageAdmin as OfficialMessage;
use Modules\Region\Entities\AreaManager;

class OfficialMessageController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'official-messages';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Official messages'))
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
            ->title(trans('official-messages'))
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
            ->title(trans('official-messages'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('official-messages'))
            ->body($this->form()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OfficialMessage);
        // Identity resolved centrally: super-admin preview honoured, real
        // managers pinned to their own id (no raw request/session override).
        $roleAuthId = Common::resolveAreaManagerId();
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->when($countryID && !$roleAuthId, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->when($roleAuthId, fn($q) => $q->where('admin_id', $roleAuthId))
            ->when(empty($roleAuthId), fn($q) => $q->whereNull('admin_id'))
            ->where('type', 2)->orderByDesc('id');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('uuid'));
            });
        });
        $grid->id(__('ID'));
        if (!$roleAuthId) {
            $grid->column('user.name', trans('user id'))->display(function ($name) {
                $uid = @$this->user->uuid;
                $path = @$this->user?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                    <strong>$name</strong><br>
                    <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                </div>
            </div>
        ";
            });
        }

        $grid->title(trans('title'));


        $grid->content(__('content'));
        $grid->feature(__('type'));
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

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(OfficialMessage::findOrFail($id));

        //        $show->id('ID');
        //        $show->title(trans('title'));
        //        $show->img('img');
        //        $show->user_id('user_id');
        //        $show->content('content');
        //        $show->type('type');
        //        $show->url('url');
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function search(Request $request)
    {
        $search = $request->get('q');

        $users = User::where('uuid', 'like', "%$search%")->get();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->uuid,
                'text' => $user->uuid . '--' . ($user->nicename ?: $user->name),
            ];
        }

        return response()->json($results);
    }

    protected function form()
    {
        $roleAuthId = Common::resolveAreaManagerId();
        $form = new Form(new OfficialMessageAdmin);
        $this->disableFormTools($form);
        if ($form->isEditing()) {
            $form->display('id', __('ID'));
        }
        $form->fieldset(__('Message Content'), function (Form $form) {
            $form->text('title', __('title'))->rules('required|max:255');
            $form->textarea('content', __('content'))->rules('required');
            $form->image('img', __('img'));
            $form->text('url', __('url'));
        });
        if (!$roleAuthId) {

            $form->fieldset(__('Targeting'), function (Form $form) {
                $form->select('admin_role', __('sender role'))->options([
                    'area_manager'   => __('area manager'),
                    'country_manager' => __('country manager'),

                ])->help(__('sender role help'))->when('area_manager', function (Form $form) {
                    $form->select('region_id', __('area'))
                        ->options('/api/search/regions')
                        ->ajax('/api/search/regions', 'id', 'name');
                })->when('country_manager', function (Form $form) {
                    $form->select('country_id', __('country'))
                        ->options('/api/search/countries')
                        ->ajax('/api/search/countries', 'id', 'name');
                });
                $form->select('type_feature', __('targeting mode'))->options([
                    'single'   => __('single audience'),
                    'multi' => __('multiple audiences'),

                ])->when('single', function (Form $form) {
                    $this->selectFeature($form);
                })->when('multi', function (Form $form) {
                    $form->multipleSelect('multi_feature', __('audiences'))
                        ->options([
                            'all' => __('all'),
                            'users' => __('regular users'),
                            'host_users' => __('hosts'),
                            'host_agencies' => __('Host Agencies'),
                            'charge_agencies' => __('charge agencies'),
                            'families' => __('families'),
                            'bds' => __('bds'),
                            'vips' => __('Vips'),
                        ])->attribute([
                            'id' => 'multi_feature_select'
                        ])
                        ->help(__('Selecting All will automatically select all other options'));
                });
            });
            Admin::script(<<<JS
            $('#multi_feature_select').on('change', function () {
                var selected = $(this).val() || [];

                // If "all" is selected
                if (selected.includes('all')) {
                    // Select all options
                    $('#multi_feature_select option').prop('selected', true);
                    $('#multi_feature_select').trigger('change.select2');
                } else {
                    // Deselect "all" if it’s not alone
                    $('#multi_feature_select option[value="all"]').prop('selected', false);
                    $('#multi_feature_select').trigger('change.select2');
                }
            });
            JS);
        } else {
            $this->selectFeatureManager($form, $roleAuthId);
        }
        $form->hidden('type', __('type'))->default(2);


        return $form;
    }

    protected function selectFeatureManager(Form $form, $authId)
    {
        $countriesIds = Common::areaCountriesV2($authId);
        $areaManager = AreaManager::find($authId);
        $countries = Country::selectRaw('concat(name, " - ", e_name) as name, id')->when(!empty($countriesIds), function ($q) use ($countriesIds) {
            $q->whereIn('id', $countriesIds);
        })
            ->pluck('name', 'id')
            ->toArray();
        $form->select('feature', __('audience'))->options([
            'agency'   => __('agency'),
            'family' => __('family'),
            'users'   => __('users'),
            'bds'  => __('BDs'),
            'shipping_agency'  => __('shipping agency')
        ])->when('agency', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('agencies scope'))->options([
                'area_country' => __('All Agencies around your region'),
                'country' => __('All Agencies in Country'),
                'ids'   => __('Specific Agency by ID'),
            ])->when('ids', function (Form $form) {
                $form->belongsToMany('agency_ids', Agencies::class, trans('agencies'));
                $form->select('member_title', __('recipients within agency'))->options([
                    'owner'   => __('owner'),
                    'admin' => __('admins'),
                    'members'   => __('members'),
                ])->default('owner');
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('agencies country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('family', function (Form $form) {
            $form->belongsToMany('feature_ids', Families::class, trans('families'));
            $form->select('member_title', __('recipients within family'))->options([
                'owner'   => __('owner'),
                'admin' => __('admins'),
                'members'   => __('members'),
            ])->default('owner');
        })->when('users', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('users scope'))->options([
                'area_country' => __('Users around your regions'),
                'country' => __('Users in Specific Country'),
                'logout'   => __('Logged Out Users'),
            ])->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('users country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('bds', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('BDs scope'))->options([
                'area_country' => __('bds around your regions'),
                'ids'   => __('bds'),
                'country' => __('Bds in Specific Country'),

            ])->when('ids', function (Form $form) {
                $form->belongsToMany('Bds_id', Bds::class, trans('Bds'));
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('BDs country'))
                    ->options($countries);
                //->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        })->when('shipping_agency', function (Form $form) use ($countries) {
            $form->select('sub_feature', __('shipping agencies scope'))->options([
                'ids'   => __('Specific shipping Agency by ID'),
                'area_country' => __('shipping agency around your regions'),
                'country' => __('Shipping Agencies in Specific Country'),
            ])->when('ids', function (Form $form) use ($countries) {
                $form->belongsToMany('shipping_agency_ids', ShippingAgencies::class, trans('agencies'));
                $form->select('member_title', __('recipients within agency'))->options([
                    'owner'   => __('owner'),
                ])->default('owner');
            })->when('country', function (Form $form) use ($countries) {
                $form->select('feature_ids', __('shipping agencies country'))
                    ->options($countries);
                // ->ajax('/api/search/countries?areaManagerId=' . Auth::id(), 'id', 'name');
            });
        });


        $form->saved(function (Form $form) {
            $model = $form->model();
            $data = request()->except(['img']);
            dispatch(new OfficialMessageJob($model, $data, ($areaManager ?? Auth::user())))->onQueue('official-message');
        });
    }

    protected function selectFeature(Form $form)
    {
        $form->select('feature', __('audience'))->options([
            'agency'   => __('agency'),
            'family' => __('family'),
            'users'   => __('users'),
            'bds'  => __('BDs'),
            'shipping_agency'  => __('shipping agency')
        ])->when('agency', function (Form $form) {
            $form->select('sub_feature', __('agencies scope'))->options([
                'all'   => __('all agencies'),
                'country' => __('All Agencies in Country'),
                'ids'   => __('Specific Agency by ID'),
            ])->when('country', function (Form $form) {
                $form->select('feature_ids', __('agencies country'))
                    ->options('/api/search/countries')
                    ->ajax('/api/search/countries', 'id', 'name');
            })->when('ids', function (Form $form) {
                $form->belongsToMany('agency_ids', Agencies::class, trans('agencies'));
                $form->select('member_title', __('recipients within agency'))->options([
                    'owner'   => __('owner'),
                    'admin' => __('admins'),
                    'members'   => __('members'),
                ]);
            })->default('owner');
        })->when('family', function (Form $form) {
            $form->belongsToMany('family_ids', Families::class, trans('families'));
            $form->select('member_title', __('recipients within family'))->options([
                'owner'   => __('owner'),
                'admin' => __('admins'),
                'members'   => __('members'),
            ])->default('owner');
        })->when('users', function (Form $form) {
            $form->select('sub_feature', __('users scope'))->options([
                'country' => __('Users in Specific Country'),
                'logout'   => __('Logged Out Users'),
            ])->when('country', function (Form $form) {
                $form->select('feature_ids', __('users country'))
                    ->options('/api/search/countries')
                    ->ajax('/api/search/countries', 'id', 'name');
            });
        })->when('bds', function (Form $form) {
            $form->select('sub_feature', __('BDs scope'))->options([
                'all' => __('All BDS'),
                'country' => __('Bds in Specific Country'),
            ])->when('country', function (Form $form) {
                $form->select('feature_ids', __('BDs country'))
                    ->options('/api/search/countries')
                    ->ajax('/api/search/countries', 'id', 'name');
            });
        })->when('shipping_agency', function (Form $form) {
            $form->select('sub_feature', __('shipping agencies scope'))->options([
                'all' => __('All Shipping Agencies'),
                'country' => __('Shipping Agencies in Specific Country'),
            ])->when('country', function (Form $form) {
                $form->select('feature_ids', __('shipping agencies country'))
                    ->options('/api/search/countries')
                    ->ajax('/api/search/countries', 'id', 'name');
            });
        });

        $form->saving(function (Form $form) {
            if (is_array($form->multi_feature)) {
                $form->multi_feature = implode(',', $form->multi_feature);
            }
        });

        $form->saved(function (Form $form) {
            $model = $form->model();
            $data = request()->except(['img']);
            dispatch(new OfficialMessageJob($model, $data, Auth::user()))->onQueue('official-message');
        });
    }
}
