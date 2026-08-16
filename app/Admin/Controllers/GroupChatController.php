<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Jobs\SendNotificationsToAllUsers;
use App\Models\GroupChat;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Public\Http\Services\UpgradeLevelServices;

class GroupChatController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'group-chat';
    public $permission_setting = "chat-setting";
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(__("group Chat"))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    public function chat_settings(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_setting);
        }
        return $content
            ->view('chat_settings');
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
            ->title(__("group Chat"))
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
            ->title(__("group Chat"))
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
            ->title(__("group Chat"))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GroupChat());
        $countryID = Common::filterCountryIds();
        $grid->model()->when($countryID, function ($query) use ($countryID) {
            $query->where(function ($q) use ($countryID) {
                $q->whereHas('user', function ($subQuery) use ($countryID) {
                    $subQuery->whereIn('country_id', $countryID);
                });
            });
        })->with([
            'user.profile',
            'user.userSetting',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.monthlyDiamondReceive',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ])->orderByDesc('id');
        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            // Row 1: User search and Message
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas('user', function ($query) use ($input) {
                        $query->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('User'))->placeholder(__('Search by name or UUID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->like('text', __('Message'));
            });

            // Row 2: User ID and Date Range
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user_id', __('User ID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->between('created_at', __('Created At'))->date();
            });
        });

        $grid->tools(function ($tools) {
            $tools->append('<a href="' . route('admin.chat.view') . '" class="btn btn-sm btn-success" style="margin-left: 10px;">
                <i class="fa fa-comments"></i> ' . __('View Chat Interface') . '
            </a>');
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('text', __('Message'))->limit(50);

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('image', __('Image'))->image('', 50, 50);
        $grid->column('parent_id', __('Parent ID'));
        $grid->column('created_at', __('Created at'))->sortable();
        $grid->column('updated_at', __('Updated at'))->sortable();
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
        $show = new Show(GroupChat::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('text', __('Message'));
        $show->field('user_id', __('User ID'));
        $show->field('image', __('Image'))->image();
        $show->field('parent_id', __('Parent ID'));
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
        $form = new Form(new GroupChat);
        $this->disableFormTools($form);

        $form->textarea('text', __('Message'))->required();
        $form->number('user_id', __('User ID'))->required();
        $form->image('image', __('Image'));
        $form->number('parent_id', __('Parent ID'));

        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        return $form;
    }

    private function canAdminSendMessages(): bool
    {
        $admin = Admin::user();

        if (!$admin || empty($admin->app_id) || $admin->app_id <= 0) {
            return false;
        }

        return $admin->user()->exists();
    }

    private function getAdminAppId(): ?int
    {
        $admin = Admin::user();

        if (!$admin || empty($admin->app_id) || $admin->app_id <= 0) {
            return null;
        }

        if (!$admin->user()->exists()) {
            return null;
        }

        return (int) $admin->app_id;
    }

    public function chatView(Content $content)
    {
        return $content
            ->title(__('Group Chat'))
            ->description(__('Manage Messages'))
            ->body(view('admin.chat.interface', [
                'canSendMessages' => $this->canAdminSendMessages(),
                'adminAppId' => $this->getAdminAppId(),
            ]));
    }

    public function updateMessage(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $request->validate([
            'id' => 'required|integer',
            'text' => 'required|string|max:1000'
        ]);

        $message = GroupChat::findOrFail($request->id);
        $message->update([
            'text' => $request->text,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Message updated successfully!')
        ]);
    }

    public function deleteMessage($id): JsonResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $message = GroupChat::findOrFail($id);
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => __('Message deleted successfully!')
        ]);
    }

    public function getMessages(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $page = $request->input('page', 1);
        $perPage = 10;
        $userId = $request->input('user_id');
        $userName = $request->input('user_name');
        $uuid = $request->input('uuid');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = GroupChat::with(['user.profile', 'parent.user']);

        // Filter by user_id
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by user name
        if ($userName) {
            $query->whereHas('user', function ($q) use ($userName) {
                $q->where('name', 'like', "%{$userName}%");
            });
        }

        // Filter by UUID
        if ($uuid) {
            $query->whereHas('user', function ($q) use ($uuid) {
                $q->where('uuid', 'like', "%{$uuid}%");
            });
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform messages to include avatar URL and parent data
        $transformedMessages = $messages->getCollection()->map(function ($message) {
            $avatarPath = $message->user->profile->avatar ?? null;
            $defaultAvatar = asset('images/default-avatar.png');
            $avatarUrl = $avatarPath ? (getImagePath($avatarPath) ?? $defaultAvatar) : $defaultAvatar;

            // Parent message data
            $parentData = null;
            if ($message->parent) {
                $parentData = [
                    'id' => $message->parent->id,
                    'text' => $message->parent->text,
                    'user_name' => $message->parent->user->name ?? __('Unknown User'),
                ];
            }

            return [
                'id' => $message->id,
                'text' => $message->text,
                'user_id' => $message->user_id,
                'image' => $message->image,
                'parent_id' => $message->parent_id,
                'parent' => $parentData,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
                'user_name' => $message->user->name ?? __('Unknown User'),
                'user_avatar' => $avatarUrl,
                'user_uuid' => $message->user->uuid ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => array_reverse($transformedMessages->toArray()),
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
            'has_more' => $messages->hasMorePages(),
            'filters' => [
                'user_id' => $userId,
                'user_name' => $userName,
                'uuid' => $uuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        if (!$this->canAdminSendMessages()) {
            return response()->json([
                'success' => false,
                'error' => __('You cannot send messages. Your admin account is not connected to an app user account.')
            ], 403);
        }

        $request->validate([
            'text' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:group_chat,id'
        ]);

        $appUserId = $this->getAdminAppId();

        $user = User::find($appUserId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => __('User account not found.')
            ], 404);
        }

        $message = GroupChat::create([
            'text' => $request->text,
            'user_id' => $appUserId,
            'parent_id' => $request->parent_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $message->load(['user.profile', 'parent.user']);

        $avatarPath = $message->user->profile->avatar ?? null;
        $defaultAvatar = asset('images/default-avatar.png');
        $avatarUrl = $avatarPath ? (getImagePath($avatarPath) ?? $defaultAvatar) : $defaultAvatar;

        // Parent message data
        $parentData = null;
        if ($message->parent) {
            $parentData = [
                'id' => $message->parent->id,
                'text' => $message->parent->text,
                'user_name' => $message->parent->user->name ?? __('Unknown User'),
            ];
        }

        $responseData = [
            'id' => $message->id,
            'text' => $message->text,
            'user_id' => $message->user_id,
            'image' => $message->image,
            'parent_id' => $message->parent_id,
            'parent' => $parentData,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
            'user_name' => $message->user->name ?? __('Admin'),
            'user_avatar' => $avatarUrl,
            'user_uuid' => $message->user->uuid ?? null,
        ];

        (new UpgradeLevelServices())->sendWorldChat($user);

        try {
            event(new \Modules\Chat\Events\GroupChat($responseData));
        } catch (\Throwable $th) {
            // Log error if needed
            // \Log::error('Pusher error: ' . $th->getMessage());
        }

        dispatchJobToQueue(
            new SendNotificationsToAllUsers($user, $request->text, $responseData),
            queueName: 'heavyProcessing'
        );

        return response()->json([
            'success' => true,
            'message' => $responseData
        ]);
    }

    /**
     * Get room image by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getRoomImage($id): JsonResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        try {
            $room = \App\Models\Room::find($id);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404);
            }

            $roomImage = $room->room_cover ?? $room->final_room_image ?? '';

            if (!empty($roomImage)) {
                $roomImage = getImagePath($roomImage);
            }

            return response()->json([
                'success' => true,
                'image' => $roomImage,
                'room_id' => $id,
                'room_name' => $room->name ?? ''
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching room image: ' . $e->getMessage()
            ], 500);
        }
    }
}
