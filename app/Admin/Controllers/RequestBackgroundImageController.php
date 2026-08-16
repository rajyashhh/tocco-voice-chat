<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Room;
use Carbon\Carbon;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Facades\CustomNotification;
use App\Models\RequestBackgroundImage;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class RequestBackgroundImageController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'request-backgrounds-image';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('request-background-image'))
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
            ->title(trans('request-background-image'))
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
            ->title(trans('request-background-image'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('request-background-image'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RequestBackgroundImage);
        $countryID = Common::filterCountryIds();
        $grid->model()
            ->with([
                'owner',
                'owner.ownerRoom',
                'owner.profile',
                'owner.country',
                'owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])
            ->when($countryID, fn($q) => $q->whereHas('owner', fn($q) => $q->whereIn('country_id', $countryID)))
            ->orderByDesc('id');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', __('status'))->select([
                    0 => __('pending'),
                    1 => __('accepted'),
                    2 => __('denied'),

                ]);
            });
        });
        $grid->id(__('ID'));
        $grid->owner_room_id(__('Room'))->display(function () {
            $owner = $this->owner ?? null;
            $ownerRoom = $owner->ownerRoom ?? null;

            $name = $ownerRoom->room_name ?? '';
            $uuid = $owner->uuid ?? '';
            $path = $ownerRoom->room_cover ?? null;
            $defaultImage = asset("images/room.jpg");
            $url = $path ? getImagePath($path) : $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40, borderRadius: 0);
            $showUrl = $ownerRoom ? url("admin/rooms/{$ownerRoom->id}") : '#';

            $escapedName = json_encode($name, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS);
            $escapedName = substr($escapedName, 1, -1); // remove surrounding quotes

            return <<<EOT
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$escapedName</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uuid</span>
                    </div>
                </div>
            EOT;
        });

        $grid->column('owner.name', __('owner'))
            ->display(function ($name) {
                $owner = $this->owner ?? null;
                $uid = $owner->uuid ?? '';
                $ownerId = $owner->id ?? null;
                $path = $owner->profile->avatar ?? null;

                $defaultImage = asset("images/businessman-icon.jpg");
                $url = $path ? getImagePath($path) : $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = $ownerId ? handleShowImageWithTypes($ownerId, $url, 40, 40) : "<img src='{$url}' width='40' height='40' style='border-radius: 50%; object-fit: cover;'>";

                $escapedName = $name ? json_encode($name, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS) : '';
                $escapedName = substr($escapedName, 1, -1); // remove quotes

                $showUrl = $ownerId ? url("admin/users/{$ownerId}") : '#';

                return <<<EOT
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        <span style='text-decoration: underline; cursor: pointer;'>$escapedName</span>
                    </a>
                    <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                </div>
            </div>
        EOT;
            });


        $grid->img(__('image'))->display(function ($img) {
            $defaultImage = asset("images/background_room.jpg");
            $path = getImagePath($img) ?? $defaultImage;
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

        $grid->column('status', __('status'))->display(function ($status) {
            $statuses = [
                0 => ['label' => __('pending'), 'color' => 'orange'],
                1 => ['label' => __('accepted'), 'color' => 'green'],
                2 => ['label' => __('denied'), 'color' => 'red'],
                3 => ['label' => __('Stoped'), 'color' => 'grey'],
            ];

            $badgeColor = $statuses[$status]['color'] ?? 'orange';
            $statusLabel = $statuses[$status]['label'] ?? __('pending');

            return "<span style='display: inline-block; padding: 5px 10px; color: white; background-color: $badgeColor; border-radius: 5px;'>
                        $statusLabel
                    </span>";
        });

        $grid->column('expair', __('Expire'))->display(function ($value) {
            if (!$value) {
                return '—';
            }

            $expairDate = Carbon::parse($value);
            $diffInDays = now()->diffInDays($expairDate, false); // false = allow negative

            if ($diffInDays > 0) {
                return "$diffInDays";
            } elseif ($diffInDays === 0) {
                return __("today");
            } else {
                return abs($diffInDays) . " " . __("days ago");
            }
        });


        $grid->column('updated_at', __('admin.updated_at'))->display(function ($date) {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        });

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        $this->extendGrid($grid);
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
        $show = new Show(RequestBackgroundImage::findOrFail($id));

        $show->id('ID');
        $show->owner_room_id('owner_room_id');
        $show->img('img')->image('', 30);
        $show->status(__('status'))->using(
            [
                0 => __('pending'),
                1 => __('accepted'),
                2 => __('denied')
            ]
        );
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RequestBackgroundImage);
        $this->disableFormTools($form);
        $form->display(__('ID'));
        // $form->display('owner_room_id', 'owner_room_id');
        $form->select('owner_room_id', __('owner room id'))->options(function () {
            $options = [];
            $users = User::query()->where('id', $this->owner_room_id)->get();
            foreach ($users as $cat) {
                $options[$cat->id] = $cat->uuid . '-' . $cat->name;
            }
            return $options;
        })->ajax('/api/search/users2', 'id', 'name')->default(2)->creationRules('required');
        // $form->select('owner_id', __('owner'))->options('/api/search/users2')->ajax('/api/search/users2', 'id', 'name');

        $form->image('img', __('img'))->creationRules('required');

        $form->select('status', __('status'))->options(
            [
                0 => __('pending'),
                1 => __('accepted'),
                2 => __('denied')
            ]
        )->default(1);


        if ($form->isCreating()) {
            $form->number('expair', __('expair'))->default(30);
        }
        $form->hidden('type')->default("admin");
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        $form->editing(function (Form $form) {
            $model = $form->model();

            if ($model->created_by_type !== \App\Models\Admin::class) {
                $form->fields()->each(function ($field) {
                    if ($field->column() === 'status') {
                        $field->help('⚠️ If you deny this background, the user will receive a refund of its cost.');
                    }
                });
            }
        });

        $form->saving(function (Form $form) {
            $model = $form->model();
            $user = User::find($model->owner_room_id);
            $status = $model->status;

            if ($form->isCreating() && $form->expair) {
                $form->expair = \Carbon\Carbon::now()->addDays($form->expair)->timestamp;
            }

            if (! $user) {
                return;
            }

            // If denied and editing, refund if not created by admin
            if ($form->isEditing() && $model->getOriginal('status') == 1 && $status == 2) {
                if ($model->created_by_type !== \App\Models\Admin::class) {
                    $cost = Common::getConfig('cost_request_background') ?: 2000;
                    DB::transaction(function () use ($user, $cost) {
                        User::where('id', $user->id)->increment('di', $cost);
                    });
                }
                CustomNotification::BackgroudRequest($user, 1); // Notify denied
            }

            // If accepted, notify
            if ($status == 1) {
                CustomNotification::BackgroudRequest($user, 0); // Notify accepted
            }
        });



        return $form;
    }
}
