<?php

namespace Modules\UsersWallet\Http\Controllers\Web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\CustomNotification;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Modules\UsersWallet\Entities\UserWithdrawal;
use Modules\UsersWallet\Entities\WalletField;
use Modules\UsersWallet\Entities\WalletLog;

class UserWithdrawalController extends MainController
{
    public $permission_name = 'user-withdrawal';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('User Withdrawals'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('User Withdrawal'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__('Edit Withdrawal'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('New Withdrawal'))
            ->body($this->form()));
    }

   
    protected function grid()
{
    $grid = new Grid(new UserWithdrawal());
    $grid->model()->orderBy('id','desc');
    $grid->column('id', __('ID'))->sortable();

 
    $grid->column('user_id', __('User'))->display(function ($name) {
        $user = $this->user;
        if (! $user) {
            return __('No User');
        }

        return app(UserService::class)->adminUserCard($user, withoutLevels: true);
    });
    Admin::style(UserService::adminUserCardStyles() . gridStyles());

    $grid->column('amount', __('Amount'))->display(function ($value) {
        return number_format($value, 2);
    });

    $grid->column('status', __('Status'))->using([
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected'),
    ]);

    $grid->column('created_at', __('Created At'))->display(function ($value) {
        return Carbon::parse($value)->format('Y-m-d H:i');
    });

   
    $grid->column('actions', __('Actions'))->display(function () {

        $approveUrl = route('admin.withdrawals.approve', $this->id);
        $rejectUrl  = route('admin.withdrawals.reject', $this->id);

        if ($this->status === 'approved') {
            return '<span class="text-success">' . __('Approved') . '</span>';
        }

        if ($this->status === 'rejected') {
            return '<span class="text-danger">' . __('Rejected') . '</span>';
        }

        $approveText = __('Approved');
        $rejectText  = __('Reject');

        return <<<HTML
            <button class="btn btn-success btn-sm approve-btn" data-url="{$approveUrl}">
                ✔ {$approveText}
            </button>

            <button class="btn btn-danger btn-sm reject-btn" data-url="{$rejectUrl}">
                ✖ {$rejectText}
            </button>
        HTML;
    })->width(160);


    Admin::script("
            document.addEventListener('DOMContentLoaded', function () {

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

                    button.addEventListener('click', function(e){
                        e.preventDefault();

                        const messages = {
                            approve: {
                                title: 'هل أنت متأكد من الموافقة على هذا السحب؟',
                                confirm: 'نعم',
                                cancel: 'إلغاء',
                                color: '#28a745'
                            },
                            reject: {
                                title: 'هل تريد رفض طلب السحب؟',
                                confirm: 'رفض',
                                cancel: 'إلغاء',
                                color: '#dc3545'
                            },
                            success: {
                                ar: 'تمت العملية بنجاح!',
                                en: 'Action completed successfully!',
                            },
                            error: {
                                ar: 'حدث خطأ أثناء العملية',
                                en: 'An error occurred!',
                            }
                        };

                        const locale = document.documentElement.lang || 'ar';

                        Swal.fire({
                            title: messages[actionType].title,
                            type: 'question',
                            showCancelButton: true,
                            confirmButtonText: messages[actionType].confirm,
                            cancelButtonText: messages[actionType].cancel,
                            confirmButtonColor: messages[actionType].color,
                            cancelButtonColor: '#6c757d',
                        }).then((result) => {

                            if(result.value){
                                const url = button.dataset.url;
                                sendRequest(url).then(res => {
                                    if(res.success){
                                        Swal.fire({
                                            title: res.message || messages.success[locale],
                                            type: 'success',
                                            timer: 1800,
                                            showConfirmButton: false
                                        });

                                        button.closest('tr').remove();
                                    } else {
                                        Swal.fire('خطأ', res.message || messages.error[locale], 'error');
                                    }
                                }).catch(() => {
                                    Swal.fire('خطأ', messages.error[locale], 'error');
                                });
                            }
                        });
                    });
                }

                document.querySelectorAll('.approve-btn').forEach(btn => handleAction(btn, 'approve'));
                document.querySelectorAll('.reject-btn').forEach(btn => handleAction(btn, 'reject'));
            });
            ");

    $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();    
            $actions->disableDelete();  
        });
        
        
        return $grid;
}

protected function detail($id)
{
    $withdrawal = UserWithdrawal::findOrFail($id);

    $show = new \Encore\Admin\Show($withdrawal);

    $show->field('id', __('ID'));

    $show->field('user_id', __('User'))->as(function ($userId) {
        $user = \App\Models\User::find($userId);
        return $user ? $user->uuid . '_' . $user->name : '-';
    });

    $show->field('amount', __('Amount'))->as(fn($v) => number_format($v, 2));

    $show->field('status', __('Status'))->as(function ($status) {
        return match($status) {
            'pending' => __('Pending'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            default => $status,
        };
    });

    $show->field('meta', __('Fields'))->unescape()->as(function () use ($withdrawal) {

        $meta = $withdrawal->meta;

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $meta = $decoded;
            }
        }

        if (!$meta || !is_array($meta)) {
            return '<p>-</p>';
        }

        $fieldIds = array_keys($meta);
        $fields = WalletField::whereIn('id', $fieldIds)->get()->keyBy('id');

        $locale = app()->getLocale();

        $html = '<table class="table table-bordered" style="width:100%;">';
        $html .= '<thead><tr><th style="width:30%">' . __('Field') . '</th><th>' . __('Value') . '</th></tr></thead><tbody>';

        foreach ($meta as $fieldId => $value) {

            $field = $fields->get($fieldId);

            $title = $fieldId;
            if ($field) {
                $rawTitle = $field->title;

                if (is_string($rawTitle)) {
                    $decodedTitle = json_decode($rawTitle, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTitle)) {
                        $rawTitle = $decodedTitle;
                    }
                }

                if (is_array($rawTitle)) {
                    $title = $rawTitle[$locale] ?? $rawTitle['en'] ?? $fieldId;
                } else {
                    $title = $rawTitle ?: $fieldId;
                }
            }

            if (is_string($value)) {
                $maybe = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($maybe)) {
                    $valueFormatted = '<pre style="white-space:pre-wrap;">' . e(json_encode($maybe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                } else {
                    $valueFormatted = e($value);
                }
            } elseif (is_array($value)) {
                $valueFormatted = '<pre style="white-space:pre-wrap;">' . e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            } else {
                $valueFormatted = e((string) $value);
            }

            $html .= sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                e($title),
                $valueFormatted
            );
        }

        $html .= '</tbody></table>';

        return $html;
    });

    $show->field('created_at', __('Created At'))->as(function ($v) {
        return Carbon::parse($v)->format('Y-m-d H:i');
    });

    return $show;
}

    
    protected function form()
    {
        $form = new Form(new UserWithdrawal());

        $form->select('user_id', __('User'))
            ->options(User::all()->pluck('name', 'id'))
            ->rules('required');

        $form->decimal('amount', __('Amount'))
            ->rules('required|numeric|min:1')
            ->default(0);

        // الحقول الديناميكية
        $form->textarea('meta', __('Meta (JSON)'))
            ->placeholder(json_encode(['account_number' => '', 'bank_name' => '']))
            ->rules('nullable|json');

        $form->select('status', __('Status'))
            ->options([
                'pending' => __('Pending'),
                'approved' => __('Approved'),
                'rejected' => __('Rejected'),
            ])
            ->default('pending');

        return $form;
    }


    public function approve($id)
    {
        $withdrawal = UserWithdrawal::findOrFail($id);
        $wallet = $withdrawal->user->userWallet;

        if ($withdrawal->status != 'pending') {
            return response()->json(['message' => 'العملية تمت مسبقاً'], 400);
        }
        $available = wallet_available_by_wallet($wallet);
        if ($available < $withdrawal->amount) {
                throw new \Exception('Insufficient balance.');
        }
        $wallet->cut_amount += $withdrawal->amount;
        $wallet->pending_amount -= $withdrawal->amount;
        $wallet->save();

     
        $withdrawal->status = 'approved';
        $withdrawal->save();
        $after_amount = wallet_available_by_user($withdrawal->user->id);
            $walletLog = WalletLog::where('related_id', $withdrawal->id)->first();
        $walletLog->update([
            'wallet_id' => $wallet->id,
            'user_id' => $withdrawal->user->id,
            'amount' => -$withdrawal->amount,
            'operation' => 'subtract',
            'type' => 'user',
            'before_amount' => $available ,
            'after_amount' => $after_amount,
           'related_id' => $withdrawal->id

        ]);
        CustomNotification::withdrawalApproved($withdrawal->user, $withdrawal->amount);

        return response()->json([ 'success'=> true ,'message' => 'تمت الموافقة على السحب بنجاح']);
    }

    public function reject($id)
    {
        $withdrawal = UserWithdrawal::findOrFail($id);
        $wallet = $withdrawal->user->userWallet;

        if ($withdrawal->status != 'pending') {
            return response()->json(['message' => 'العملية تمت مسبقاً'], 400);
        }
        $available = wallet_available_by_wallet($wallet);

        $wallet->pending_amount -= $withdrawal->amount;
        $wallet->save();

        $withdrawal->status = 'rejected';
        $withdrawal->save();
        $after_amount = wallet_available_by_user($withdrawal->user->id);

        $walletLog = WalletLog::where('related_id', $withdrawal->id)->first();
        $walletLog->update([
            'wallet_id' => $wallet->id,
            'user_id' => $withdrawal->user->id,
            'amount' => $withdrawal->amount,
            'operation' => 'add',
            'type' => 'user',
            'before_amount' => $available,
            'after_amount' => $after_amount,
            'related_id' => $withdrawal->id
        ]);
        CustomNotification::withdrawalRejected($withdrawal->user, $withdrawal->amount);

        return response()->json(['success'=> true ,'message' => 'تم رفض الطلب وإزالة المبلغ من المعلّق']);
    }


}
