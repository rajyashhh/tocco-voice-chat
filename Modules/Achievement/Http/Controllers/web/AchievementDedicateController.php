<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Selectables\AllUsers;
use App\Selectables\CustomAchievements;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\Achievement\Entities\UserAchievementLevel;

class AchievementDedicateController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'gift-a-medal';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Gift a Badge'))
            ->body($this->grid()));
    }

    public function create(Content $content)
    {

        return parent::create($content
            ->title(trans('user-achievement-levels'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserAchievementLevel());
        $countryID = Common::filterCountryIds();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('uuid', request('uuid'));
                });
            }, __('uuid'), 'uuid');
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($from = request('from_date')) {
                    }
                }, __('From Date'), 'from_date')->date();

                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });
        $grid->model()->with([
            'user.profile',
            'user',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'admin',
            'customAchievement',
            'customAchievement.images',
        ])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->when(
                request('from_date') && request('to_date'),
                function ($q) {
                    $start = Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))->startOfDay();
                    $end = Carbon::parse(convertArabicToEnglishNumbers(request('to_date')))->endOfDay();
                    $q->whereBetween('created_at', [$start, $end]);
                }
            )
            ->when(
                request('uuid'),
                function ($q) {
                    $q->whereHas('user', function ($u) {
                        $u->where('uuid', request('uuid'));
                    });
                }
            )
            ->where(function ($q) {
                $q->whereNotNull('custom_image')
                    ->orWhereNotNull('file')->orWhereNotNull('custom_achievement_id');
            })
            ->orderByDesc('id');

        $grid->column('id', __('Id'));
        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('admin.name', __('creator'))->display(function () {

            $name = $this->admin->name ?? '';
            $id = $this->admin->id ?? 0;
            if (request()->filled('_export_')) {
                return "{$name} (ID: {$id})";
            }
            $path = @$this->admin->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = $path ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            // Validate admin existence before accessing id
            $showUrl = '#'; // Default to prevent broken links
            if ($this->admin && $this->admin->id) {
                $showUrl = url("admin/auth/users/{$this->admin->id}");
            }

            return "
             <div style='display: flex; align-items: center; gap: 10px;'>
                 <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                     $image
                     <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                 </a>
             </div>
             ";
        });
        if (!request()->filled('_export_')) {
            $grid->column('file', __('image'))->display(function ($img) {
                $defaultImage = asset("images/background_room.jpg");
                $path = getImagePath($img ?? $this->custom_image ?? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image);
                if (!isImageExists($path)) {
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
            $states = [
                'off' => ['value' => 0, 'text' => 'no', 'color' => 'danger'],
                'on' => ['value' => 1, 'text' => 'yes', 'color' => 'success'],
            ];
            if (Admin::user()->can('edit-' . $this->permission_name) || Admin::user()->can('*')) {
                $grid->column('is_enable', __('is enabled'))->switch($states);
            }
        } else {
            $grid->column('is_enable', __('is enabled'))->display(function ($isEnable) {
                return   $isEnable == 1 ? __('on') : __('off');
            });
        }


        $grid->column('created_at', trans('admin.created_at'));


        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableView();
            $actions->disableEdit();
        });
        $this->extendGrid($grid);
        $grid->disableExport();

        return $grid;
    }


    protected function form()
    {
        $form = new Form(new UserAchievementLevel());
        $form->html('<div class="full-column-width">');
        //    $form->belongsTo('user_id', AllUsers::class, trans('user'));
        $form->html(function () use ($form) {

            return view('admin.grid.users.UserAchievementLevelDedicate');
        });


        $form->belongsTo('custom_achievement_id', CustomAchievements::class, trans('Custom achievement'));
        $form->hidden('admin_id', __('is_frozen'))->default(auth()->id());
        $form->hidden('receive_type', __('is_frozen'))->default('admin_dedication');
        $form->html('</div>');


        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');

        $form->saving(function (Form $form) {
            $userId = request('user_id');
            if (!$userId) {
                throw new \Exception('Please select a user!');
            }
            // ensure the model gets the user_id so it's included in the DB insert
            $form->model()->user_id = $userId;
        });
        return $form;
    }
}
