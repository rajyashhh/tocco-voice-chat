<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Modules\Country\Entities\SubAdmin;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends EncorUsersController
{
    // \Encore\Admin\Controllers\UserController

    protected $model;

    public $permission_name = 'auth-users';

    public function __construct()
    {
        $userModel = Admin::class;
        $this->model = new $userModel;
    }

    public function store()
    {
        $result = parent::store();

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        admin_toastr(__('Save succeeded !'), 'success');
        return redirect()->back();
    }

    public function edit($id, Content $content)
    {

        return parent::edit($id, $content);
    }

    public function grid()
    {
        $permission_name = $this->permission_name;

        $grid = new \Encore\Admin\Grid(new \App\Models\Admin());
        $authId = auth()->user()->type == 'country' ? auth()->user()->id : auth()->user()->parent_id;

        $grid->model()->where(function ($q) use ($authId) {
            $q->where('parent_id', $authId);
        })
            ->with(['createdBy', 'roles'])
            ->where('is_preview', 0)
            ->where('type', 'sub_country')
            ->whereDoesntHave('roles', function ($query) {
                $query->where('slug', 'agency-owner');
            });

        $grid->column('id', 'ID')->sortable();
        $grid->column('username', trans('admin.username'))->sortable();
        $grid->column('name', trans('admin.name'))->sortable();
        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();

        $grid->column('createdBy.name', __('created by'))->display(function () {
            try {
                $user = $this->createdBy;
                $name = $user->name ?? '';

                if (request()->filled('_export_')) {
                    return $name;
                }
                if (!$user) return "<span style='color: red;'>غير مرتبط</span>";

                $id = $user->id ?? 'غير معروف';
                $path = $user->avatar ?? null;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id . '_created_by', $url, 40, 40);
                $showUrl = url("superadmin/superadmin-profile/{$user->id}");

                return "
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        $image
                        <div>
                           <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                             <span style='text-decoration: underline; cursor: pointer;'>" . e($name) . "</span>
                            </a>
                            <span style='font-size: smaller;'>ID: " . e($id) . "</span>
                        </div>
                    </div>
                ";
            } catch (\Exception $e) {
                \Log::error('Error in createdBy column display', ['error' => $e->getMessage()]);
                return '<span style="color: red;">خطأ في العرض</span>';
            }
        });

        // إضافة عمود مخصص للأفعال يحتوي على جميع الأزرار
        $grid->column('custom_actions', 'الإجراءات')->display(function () {
            try {
                $id = (int) $this->id;
                $viewUrl = url("superadmin/auth-users/{$id}");
                $editUrl = url("superadmin/auth-users/{$id}/edit");

                return "
                <div class='btn-group'>
                    <button type='button' class='btn btn-sm btn-default dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                        <i class='fa fa-cog'></i>&nbsp;&nbsp;<span class='caret'></span>
                    </button>
                    <ul class='dropdown-menu dropdown-menu-right'>
                        <li><a href='{$viewUrl}'><i class='fa fa-eye'></i>&nbsp;&nbsp;عرض</a></li>
                        <li><a href='{$editUrl}'><i class='fa fa-edit'></i>&nbsp;&nbsp;تعديل</a></li>
                        <li><a href='javascript:void(0);' onclick='customSuperAdminDelete({$id})' style='color: red;'><i class='fa fa-trash'></i>&nbsp;&nbsp;حذف</a></li>
                    </ul>
                </div>";
            } catch (\Exception $e) {
                \Log::error('Error in custom_actions column display', ['error' => $e->getMessage()]);
                return '<span style="color: red;">-</span>';
            }
        })->sortable(false);

        // تعطيل الأفعال الافتراضية تماماً
        $grid->disableActions();

        $grid->tools(function ($tools) {
            $logoutUrl = route('superadmin.superadmin.logout');
            $loginText = __('login');
            $areaManagerUrl = url('/superadmin/login');

            $customButtonHTML = <<<HTML
                <div style="display: contents; align-items: center;">
                    <a href="{$logoutUrl}" class="btn btn-sm btn-danger" style="margin-right: 10px;">
                        <i class="fa fa-sign-in"></i> {$loginText}
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="copyAreaManagerUrl()">
                        <i class="fa fa-copy"></i>
                    </button>

                </div>
                <script>
                    if (typeof copyAreaManagerUrl === 'undefined') {
                        function copyAreaManagerUrl() {
                            const url = '{$areaManagerUrl}';
                            navigator.clipboard.writeText(url).then(() => {
                                toastr.success('تم نسخ الرابط بنجاح');
                            }).catch(() => {
                                alert('تعذر نسخ الرابط');
                            });
                        }
                    }
                    
                    if (typeof customSuperAdminDelete === 'undefined') {
                        function customSuperAdminDelete(id) {
                            console.log('customSuperAdminDelete called with id:', id);
                            
                            if (confirm('هل أنت متأكد من حذف هذا المستخدم؟')) {
                                console.log('Delete confirmed, sending AJAX to: /superadmin/auth-users/' + id);
                                
                                // الحصول على CSRF token بطريقة آمنة
                                var csrfToken = '';
                                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                                if (csrfMeta) {
                                    csrfToken = csrfMeta.getAttribute('content');
                                } else if (window.Laravel && window.Laravel.csrfToken) {
                                    csrfToken = window.Laravel.csrfToken;
                                } else if ($('meta[name="csrf-token"]').length) {
                                    csrfToken = $('meta[name="csrf-token"]').attr('content');
                                }
                                
                                console.log('CSRF Token:', csrfToken);
                                
                                fetch('/superadmin/auth-users/' + id, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => {
                                    console.log('Response status:', response.status);
                                    return response.json();
                                })
                                .then(data => {
                                    console.log('Delete response:', data);
                                    if (data.success) {
                                        toastr.success('تم الحذف بنجاح');
                                        location.reload();
                                    } else {
                                        toastr.error(data.message || 'حدث خطأ');
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete error:', error);
                                    toastr.error('حدث خطأ أثناء الحذف');
                                });
                            }
                        }
                    }
                </script>
                HTML;

            $tools->append($customButtonHTML);
        });

        return $grid;
    }

    public function update($id)
    {
        $user = Admin::query()->findOrFail($id);
        if (\request('password') != $user->password || \request('username') != $user->username) {
            Agent::where("id", $user->id)->update([
                "remember_token" => null
            ]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $result = parent::update($id);

        // Clear permissions cache for this user (important for Octane)
        Cache::forget('admin_user_permissions_' . $id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        try {
            $user = $this->model->find($id);
            if ($user) {
                if ($user->isRole('admin') || $user->isRole('developer')) {
                    \Log::warning('Attempted to delete admin/developer user', ['user_id' => $id]);
                    return response()->json(['error' => true, 'message' => __('admin cant be deleted')]);
                }
            }

            $OldUserAppId = User::find($user->app_id);
            if ($OldUserAppId) {
                $OldUserAppId->is_sub_super_admin = 0;
                $OldUserAppId->save();

            }

            //  Agency::query ()->where ('owner_id',$id)->delete ();

            // حذف المستخدم
            $user->delete();


            return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح']);
        } catch (\Exception $e) {
            \Log::error('Error in AdminUserController destroy method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => true, 'message' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage()]);
        }
    }

    public function form()
    {

        $form =  parent::form();
        $form->select('app_id', __('validation.select_user'))->options(function ($value) {
            $ops2 = [];
            foreach (User::Where('id', $value)->get() as $user) {
                $ops2[$user->id] = $user->uuid . '_' . $user->name;
            }
            return $ops2;
        })->ajax('/api/search/users-subsuperadmin', 'id', 'name')->rules('required');

        $form->saved(function (Form $form) {
            // Clear permissions cache for the saved user (important for Octane)
            if ($form->model()->id) {
                Cache::forget('admin_user_permissions_' . $form->model()->id);
            }
        });

        return $form;
    }



    public function showSubSuperAdmin($id)
    {
        $scopeRoot = auth()->user()->type == 'country' ? auth()->user()->id : auth()->user()->parent_id;
        $subSuperAdmin = SubAdmin::with('appUser')->where('parent_id', $scopeRoot)->find($id);

        if (!$subSuperAdmin) {
            return response()->json([
                'status' => 404,
                'message' => trans('message.notFoundGift'),
            ]);
        }

        $item = $subSuperAdmin->toArray();
        $item['app_user_name'] = null;
        if ($subSuperAdmin->appUser) {
            $appUser = $subSuperAdmin->appUser;
            $display = trim(($appUser->name ?? '') . ' - ' . ($appUser->uuid ?? ''));
            $item['app_user_name'] = $display;
        }

        return response()->json([
            'status' => 200,
            'item' => $item,
        ]);
    }

    public function updateSubSuperAdmin(Request $request,)
    {

        $scopeRoot = auth()->user()->type == 'country' ? auth()->user()->id : auth()->user()->parent_id;
        $subSuperAdmin = SubAdmin::where('parent_id', $scopeRoot)->findOrFail($request->id);
        $subSuperAdmin->name = $request->name;
        $subSuperAdmin->username = $request->username;
        if ($request->has('image')) {
            $image = Common::upload('images', $request->image);
            $subSuperAdmin->avatar = $image;
        }
        $plainPassword = $request->password; // input from user
        $hash = $subSuperAdmin->password;
        if (Hash::check($plainPassword, $hash)) {

            $subSuperAdmin->password = Hash::make($request->password);
        }
        if ($request->user_id != $subSuperAdmin->app_id) {
            
            $oldUser = User::find($subSuperAdmin->app_id);
            if ($oldUser) {
                $oldUser->is_sub_super_admin = 0;
                $oldUser->save();
            }
            $subSuperAdmin->app_id = $request->user_id;
            $appUser = User::find($request->user_id);
            if ($appUser) {
                $appUser->is_sub_super_admin = 1;
                $appUser->save();
            }
        }
        $subSuperAdmin->save();
        return Redirect::back();
    }
}
