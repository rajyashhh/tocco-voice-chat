<?php

namespace App\Bd\Controllers;


use App\Admin\Services\UserService;
use App\Services\AppFeatureService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Form\Entities\FormRequest;
use Modules\Form\Services\FormRenderService;

class RequestAgencyController extends AdminController
{
    // public $permission_name = 'agencies-request';

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }

    public function index(Content $content)
    {
        $type = 'host_agency';

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
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'template',
                'bd'
            ])->where('bd_id', auth()->id())
            ->where('form_template_type', $type)->orderByDesc('id');

        $grid->column('user', __('user'))
            ->display(function ($name) {

                $user = $this->user;
                if (!$user) {
                    return '';
                }

                return app(UserService::class)->adminUserCard($user);
            });

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

        Admin::style(UserService::adminUserCardStyles() . gridStyles() . '
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
            $approveUrl = url("bd/requests/{$this->id}/approve");
            $rejectUrl = url("bd/requests/{$this->id}/reject");
            $showUrl = url("bd/request-agencies/{$this->id}");

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
            ///  if (Admin::user()->can('show-' . $this->permission_name) || Admin::user()->can('*')) {
            $html .= <<<HTML
                    <a href="{$showUrl}" class="btn btn-info btn-sm me-1">
                        <i class="fa fa-eye"></i>
                    </a>
                HTML;
            //  }

            // if (Admin::user()->can('approve-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $html .= <<<HTML
                    <button class="btn btn-success btn-sm approve-btn me-1" data-url="{$approveUrl}">
                        <i class="fa fa-check"></i>
                    </button>
                HTML;
            // }

            // if (Admin::user()->can('reject-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $html .= <<<HTML
                    <button class="btn btn-danger btn-sm reject-btn" data-url="{$rejectUrl}">
                        ✖
                    </button>
                HTML;
            // }

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
        $formRequest = FormRequest::where('id', $id)
            ->where('bd_id', auth()->id())
            ->firstOrFail();

        $show = new Show($formRequest);
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
