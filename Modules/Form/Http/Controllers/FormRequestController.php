<?php

namespace Modules\Form\Http\Controllers;


use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Models\Agency;
use App\Models\Bd;
use App\Models\Country;
use App\Models\ShippingAgency;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Form\Entities\FormRequest;
use Modules\Form\Services\FormRenderService;
use Modules\Milestones\Helpers\MilestoneHelper;
use App\Facades\CustomNotification;

class FormRequestController extends MainController
{
    protected $title;
    public $permission_name = 'form-request';

    public function __construct()
    {
        $this->title = __('requests_title');
    }


    public function index(Content $content)
    {
        $type = request()->get('type', 'host_agency');

        $pendingCounts = FormRequest::where('status', 'pending')
            ->selectRaw('form_template_type, COUNT(*) as count')
            ->groupBy('form_template_type')
            ->pluck('count', 'form_template_type')
            ->toArray();

        $buttons = [
            'host_agency' => __('Host Agency'),
            'bd_form' => __('BD Form'),
            'shipping_agency' => __('Shaping Agency'),
        ];

        Admin::style('
            .tab-btn {
                position: relative;
            }
            .tab-btn .pending-badge {
                position: absolute;
                top: -8px;
                right: -8px;
                background-color: var(--primary-color);
                filter: brightness(1.5);
                color: var(--text-secondary-color);
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 10px;
                min-width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            .tab-btn.active-tab {
                background: var(--primary-color);
                color: white;
            }
            .tab-btn .pending-badge.pulse {
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--primary-color) 70%, transparent); }
                70% { box-shadow: 0 0 0 10px color-mix(in srgb, var(--primary-color) 0%, transparent); }
                100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--primary-color) 0%, transparent); }
            }
        ');

        $header = '<div style="margin-bottom:15px; display: flex; gap: 10px;">';
        foreach ($buttons as $key => $label) {
            $activeClass = $type === $key ? 'active-tab' : '';
            $count = $pendingCounts[$key] ?? 0;

            // Badge HTML - only show if count > 0
            $badge = '';
            if ($count > 0) {
                $pulseClass = $count > 0 ? 'pulse' : '';
                $badge = "<span class='pending-badge {$pulseClass}'>{$count}</span>";
            }

            $header .= "<a href='?type={$key}' class='btn tab-btn {$activeClass}' style='position: relative;'>{$label}{$badge}</a>";
        }
        $header .= '</div>';

        $content->title(__('requests_title'));

        if (Admin::user()->can('type-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $content->row($header);
        }

        $content->row($this->getGrid($type)->render());

        return $content;
    }

    protected function getGrid($type)
    {
        $grid = new Grid(new FormRequest());

        $grid->model()
            ->select(['id', 'name', 'bd_id', 'whatsapp_number', 'submitted_by', 'form_template_type', 'status'])
            ->with([
                'user',
                'user.profile',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'template',
                'bd'
            ])
            ->where('form_template_type', $type)->orderByDesc('id');

        $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('name', __('name'));

        if ($type != 'bd_form' && $type != 'shipping_agency') {
            $grid->column('bd_id', __('Bd'))->display(function ($name) {
                if (request()->filled('_export_')) {
                    return $name;
                }
                if (!$this->bd) {
                    return '-';
                }

                $id = $this->bd->id ?? '-';
                $name = $this->bd?->username ?? 'غير معروف';
                $path = $this->bd?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->bd?->id, $url, 40, 40);
                $showUrl = url("admin/usersBd/{$this->bd?->id}");

                return "
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        $image
                        <div>
                           <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                             <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                            </a>
                            <span style='font-size: smaller;'>ID: $id</span>
                        </div>
                    </div>
                ";
            });
            $grid->column('whatsapp_number', __('whatsapp_number'))->display(fn($v) => $v ?? '-');
        }
        if ($type == 'bd_form') {
            $grid->column('country', __('country'))->display(fn($v) => $v ?? '-');
        }

        Admin::style('
            .grid-table .label-default {
                background-color: var(--primary-color) !important;
                color: #fff !important;
            }
        ');

        $grid->column('status', __('status'))->label([
            'pending' => 'default',
            'approved' => 'success',
            'rejected' => 'danger'
        ])->display(function ($status) {
            return __($status);
        });

        $grid->column('actions', __('Actions'))->display(function () {
            $approveUrl = admin_url("requests/{$this->id}/approve");
            $rejectUrl = admin_url("requests/{$this->id}/reject");
            $showUrl = admin_url('form-requests', $this->id);

            if ($this->status === 'rejected') {
                return '<span class="text-danger">' . __('Rejected') . '</span>';
            }

            if ($this->status === 'approved') {
                return '<span class="text-success">' . __('Approved') . '</span>';
            }

            $approveText = __('Approved');
            $rejectText = __('Reject');
            $viewText = __('Preview');

            $html = '';
            if (Admin::user()->can('show-' . $this->permission_name) || Admin::user()->can('*')) {
                $html .= <<<HTML
                    <a href="{$showUrl}" class="btn btn-info btn-sm me-1">
                        <i class="fa fa-eye"></i>
                    </a>
                HTML;
            }

            if (Admin::user()->can('approve-switch-' . $this->permission_name) || Admin::user()->can('*')) {
                $html .= <<<HTML
                    <button class="btn btn-success btn-sm approve-btn me-1" data-url="{$approveUrl}">
                        <i class="fa fa-check"></i>
                    </button>
                HTML;
            }

            if (Admin::user()->can('reject-switch-' . $this->permission_name) || Admin::user()->can('*')) {
                $html .= <<<HTML
                    <button class="btn btn-danger btn-sm reject-btn" data-url="{$rejectUrl}">
                        ✖
                    </button>
                HTML;
            }

            return $html;
        });

        Admin::script("
            function initFormRequestActions() {

                function sendRequest(url) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': LA.token,
                            'Accept': 'application/json',
                        },
                    }).then(res => res.json());
                }

                function handleAction(button, actionType) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        const messages = {
                            approve: {
                                title: 'هل أنت متأكد من الموافقة على هذا الطلب؟',
                                confirm: 'نعم',
                                cancel: 'إلغاء',
                                color: '#28a745'
                            },
                            reject: {
                                title: 'هل أنت متأكد من رفض هذا الطلب؟',
                                confirm: 'نعم',
                                cancel: 'إلغاء',
                                color: '#dc3545'
                            },
                            success: {
                                en: 'Action completed successfully!',
                                ar: 'تمت العملية بنجاح!',
                                hi: 'क्रिया सफलतापूर्वक पूरी हुई!',
                                tr: 'İşlem başarıyla tamamlandı!'
                            },
                            error: {
                                en: 'An error occurred!',
                                ar: 'حدث خطأ أثناء العملية',
                                hi: 'एक त्रुटि हुई!',
                                tr: 'İşlem sırasında hata oluştu!'
                            }
                        };

                        const locale = document.documentElement.lang || 'ar';

                        Swal.fire({
                            title: messages[actionType].title,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: messages[actionType].confirm,
                            cancelButtonText: messages[actionType].cancel,
                            confirmButtonColor: messages[actionType].color,
                            cancelButtonColor: '#6c757d',
                        }).then((result) => {
                            if (result.value) {
                                const url = button.dataset.url;

                                Swal.fire({
                                    title: 'جاري التنفيذ...',
                                    allowOutsideClick: false,
                                    didOpen: () => Swal.showLoading()
                                });

                                sendRequest(url)
                                    .then(res => {
                                        Swal.close();

                                        if (res.success) {
                                            Swal.fire({
                                                title: res.message || messages.success[locale],
                                                icon: res.icon || 'success', // 💡 Use icon from backend
                                                timer: 2000,
                                                showConfirmButton: false
                                            });

                                            // Reload grid without full refresh
                                            $.pjax.reload('#pjax-container');
                                        } else {
                                            Swal.fire('خطأ', res.message || messages.error[locale], 'error');
                                        }
                                    })
                                    .catch((err) => {
                                        console.error('Fetch error:', err);
                                        Swal.fire('خطأ', messages.error[locale], 'error');
                                    });
                            }
                        });
                    });
                }

                document.querySelectorAll('.approve-btn').forEach(btn => handleAction(btn, 'approve'));
                document.querySelectorAll('.reject-btn').forEach(btn => handleAction(btn, 'reject'));
            }

            initFormRequestActions();

            $(document).off('pjax:end').on('pjax:end', function() {
                initFormRequestActions();
            });
        ");


        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableColumnSelector();
        $grid->disableRowSelector();

        return $grid;
    }


    protected function form()
    {
        $form = new Form(new FormRequest());

        $form->display('user.name', __('name'));
        $form->display('bd_id', __('bd_id'));
        $form->display('agency_name', __('agency_name'));
        $form->display('whatsapp_number', __('whatsapp_number'));
        $form->display('country', __('country'));
        $form->display('form_template_type', __('form_template_type'));
        $form->display('status', __('status'));
        $form->textarea('data', __('additional_info'))->readonly();

        return $form;
    }

    /**
     * Resolve a form request scoped to the acting portal, enforcing the
     * endpoint permission on the actor rather than trusting the raw id. A BD
     * may only touch requests it owns (bd_id); the admin portal is gated by
     * the resource permission. Out-of-scope ids resolve to 404.
     */
    protected function resolveScopedRequest($id): FormRequest
    {
        $actor = Admin::user();

        $query = FormRequest::query()->where('id', $id);

        if ($actor->type === 'bd') {
            $query->where('bd_id', $actor->id);
        } else {
            Permission::check('approve-switch-' . $this->permission_name);
        }

        return $query->firstOrFail();
    }

    public function approve($id)
    {
        $request = $this->resolveScopedRequest($id);
        switch ($request->form_template_type) {
            case 'host_agency':
                return $this->approveHostAgency($request);

            case 'bd_form':
                return $this->approveBdForm($request);

            case 'shipping_agency':
                return $this->approveShapingAgency($request);

            default:
                admin_toastr(__('Unknown form type'), 'error');
                return redirect()->back();
        }
    }


    protected function approveHostAgency($request)
    {

        $owner = User::where('id', $request->submitted_by)->first();

        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => __('user_not_found')
            ], 404);
        }
        if ($owner->is_super_admin || $owner->is_sub_super_admin) {
            return response()->json([
                'success' => false,
                'key' => 'user_is_super_admin',
                'message' => __('user is country manager'),
            ], 400);
        }

        if ($this->checkUserAlreadyOwnsEntity($owner, Agency::class)) {
            return response()->json([
                'success' => false,
                'key' => 'user_already_has_agency',
                'message' => __('user_already_has_agency'),
            ], 400);
        }
        $bd = Bd::find($request->bd_id);

        // Resolve country_id: prefer owner's, fallback to BD's
        $countryId = @$owner?->country?->id ?? @$bd?->country?->id ?? null;
        
        Agency::create([
            'name' => $request->name,
            'phone' => $request->whatsapp_number,
            'app_owner_id' => $owner->id,
            'bd_id' => $bd?->id,
            'country_id' => $countryId,
        ]);
        $request->update(['status' => 'approved']);
        $owner->type_user = 2;
        $owner->save();
        MilestoneHelper::grantMilestoneToUser($owner, 'host-agency-owner');
        CustomNotification::formRequestApproved($owner, 'host_agency');
        // return response()->json(['success' => true, 'message' => __('تمت الموافقة بنجاح')]);
        return response()->json([
            'success' => true,
            'message' => __('تمت الموافقة بنجاح'),
        ]);
    }

    protected function approveBdForm($request)
    {
        $data = $request->data;

        if (is_string($data)) {
            $data = json_decode(trim($data, '"'), true);
        }
        $phoneUser = User::where('id', $request->submitted_by)->first();

        if (!$phoneUser) {
            return response()->json([
                'success' => false,
                'message' => __('user_not_found')
            ], 404);
        }

        if ($this->checkUserAlreadyOwnsEntity($phoneUser, Bd::class)) {
            return response()->json([
                'success' => false,
                'key' => 'user_already_has_bd',
                'message' => __('user_already_has_bd'),
            ], 400);
        }

        Bd::create([
            'username' => $request->name,
            'app_id' => $phoneUser->id,
            'country_id' => $phoneUser->country_id,
            'password' => $data['password'] ?? 123456789,
        ]);
        MilestoneHelper::grantMilestoneToUser($phoneUser, 'bd');
        $request->update(['status' => 'approved']);

        CustomNotification::formRequestApproved($phoneUser, 'bd_form', [
            'username' => $request->name,
            'password' => $data['password'] ?? 123456789
        ]);

        //  return response()->json(['success' => true, 'message' => __('تمت الموافقة بنجاح')]);
        return response()->json([
            'success' => true,
            'message' => __('تمت الموافقة بنجاح'),
        ]);
    }

    protected function approveShapingAgency($request)
    {
        $owner = User::where('id', $request->submitted_by)->first();

        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => __('user_not_found')
            ], 404);
        }

        if ($owner->is_super_admin || $owner->is_sub_super_admin) {
            return response()->json([
                'success' => false,
                'key' => 'user_is_super_admin',
                'message' => __('user is country manager'),
            ], 400);
        }

        if ($this->checkUserAlreadyOwnsEntity($owner, ShippingAgency::class)) {

            return response()->json([
                'success' => false,
                'key' => 'user_already_has_shipping_agency',
                'message' => __('user_already_has_shipping_agency'),
            ], 400);
        }

        $bd = Bd::find($request->bd_id);

        // Resolve country_id: prefer owner's, fallback to BD's
        $countryId = @$owner?->country?->id ?? @$bd?->country?->id ?? null;

        $shippingAgency = ShippingAgency::create([
            'name' => $request->name,
            'phone' => $request->whatsapp_number,
            'app_owner_id' => $owner->id,
            'bd_id' => $bd?->id,
            'country_id' => $countryId,
            'type'=> 2,
        ]);

        // Server-side letter avatar: shipping agencies always have a real stored image.
        if (empty($shippingAgency->img)) {
            $generated = app(\App\Services\LetterAvatarService::class)
                ->generate('agency', $shippingAgency->name, $shippingAgency->id);
            if ($generated) {
                $shippingAgency->update(['img' => $generated]);
            }
        }


        MilestoneHelper::grantMilestoneToUser($owner, 'charge-agency-owner');
        $request->update(['status' => 'approved']);
        CustomNotification::formRequestApproved($owner, 'shipping_agency');
        return response()->json(['success' => true, 'message' => __('تمت الموافقة بنجاح')]);
    }


    protected function checkUserAlreadyOwnsEntity(User $user, string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Invalid model class: {$modelClass}");
        }

        $model = new $modelClass;
        $table = $model->getTable();

        if (!\Schema::hasTable($table)) {
            throw new \RuntimeException("Table for model {$modelClass} does not exist.");
        }

        $columns = \Schema::getColumnListing($table);
        $validFields = array_intersect(['app_owner_id', 'app_id'], $columns);

        foreach ($validFields as $field) {
            if ($modelClass::where($field, $user->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function reject($id)
    {
        $actor = Admin::user();

        $query = FormRequest::query()->where('id', $id);

        if ($actor->type === 'bd') {
            $query->where('bd_id', $actor->id);
        } else {
            Permission::check('reject-switch-' . $this->permission_name);
        }

        $request = $query->firstOrFail();
        $request->status = 'rejected';
        $request->save();

        if ($user = User::find($request->submitted_by)) {
            CustomNotification::formRequestRejected($user, $request->form_template_type);
        }

        return response()->json([
            'success' => true,
            'message' => __('admin.rejected_success'),
            'icon' => 'success',
        ]);
        admin_toastr(__('rejected_message'), 'error');
        return redirect()->back();
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
        return parent::show(
            $id,
            $content
                ->title(trans(''))
                ->body($this->detail($id))
        );
    }

    protected function detail($id)
    {
        $show = new Show(FormRequest::findOrFail($id));
        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableList();
            $tools->disableDelete();
        });

        $renderer = new FormRenderService();

        $show->field('data', __('dodo'))->as(function ($jsonData) use ($renderer) {
            $data = json_decode($jsonData, true);
            return $renderer->renderFormData($data);
        })->unescape();

        return $show;
    }
}
