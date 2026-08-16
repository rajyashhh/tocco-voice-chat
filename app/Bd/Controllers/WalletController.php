<?php

namespace App\Bd\Controllers;

use App\Enums\UserCoinLogType;
use App\Helpers\ShippingAgencyHelper;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\UserCommon;
use App\Models\Bd;
use App\Models\User;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\BDSallary;
use App\Models\BdSalary;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\ShippingAgency;
use App\Services\WalletService;
use Encore\Admin\Layout\Content;
use App\Models\WalletTransaction;
use App\Services\BDChargeService;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\AdminController;
use Modules\Wallet\Services\CheckSystemConfigs;
use Modules\Wallet\Services\CheckUserExistence;
use Encore\Admin\Controllers\HasResourceActions;
use Modules\SalaryTransaction\Entities\ChargeAgency;

class WalletController extends MainController
{
    use HasResourceActions;

    protected $title = "Charges";

    public $permission_name = "browse-get-salary-bd";

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $netvalue = UserWallet::where('user_id', Auth::user()->id)
            ->selectRaw('SUM(value) as total_value, SUM(cut_amount) as total_cut')
            ->first();

        $finalvalue = ($netvalue->total_value ?? 0) - ($netvalue->total_cut ?? 0);

        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))

            ->row(function ($row) use ($finalvalue) {
                // الكارت سيتم تضمينه من Blade View
                $row->column(12, view('admin.grid.bd.wallet', ['finalSalary' => $finalvalue]));
            })

            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new \App\Models\WalletTransaction());

        $currentUserId = \Auth::user()->id;
        $grid->model()->where('user_id', $currentUserId);

        $grid->column('id', __('Id'));

        $grid->column('user.name', __('User'))->display(function () {
            $name = $this->user?->name ?? '';
            $uid = $this->user?->uuid ?? '';
            $path = $this->user?->profile?->avatar ?? null;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->user->id ?? 0, $url, 40, 40);
            $showUrl = $this->user ? url("admin/users/{$this->user->id}") : "#";

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });

        $grid->column('type', __('Type'))->display(function ($type) {
            $types = [
                'add' => __('Add'),
                'cut' => __('Cut'),
                'pending' => __('Pending'),
            ];
            return $types[$type] ?? __('Unknown');
        });

        $grid->column('value', __('Value'));

        $grid->column('description_data', __('Description'))->display(function () {
            $description = $this->description ?? '';
            $data = json_decode($this->description_data, true) ?? [];

            switch ($description) {
                case 'target_achieved':
                    $targetId = $data['target_id'] ?? null;
                    $target = \App\Models\Target::find($targetId);
                    if ($target) {
                        $targetUrl = url("admin/targets/{$target->id}");
                        return "
                            <div>
                                <a href='{$targetUrl}'>
                                    <span>" . __('Target') . ": {$target->diamonds}</span>
                                </a>
                            </div>
                        ";
                    }
                    return __('Target not found');

                case 'transfer_to_user':
                    $userId = $data['receiver_id'] ?? null;
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $path = $user->profile?->avatar ?? null;
                        $defaultImage = asset("images/businessman-icon.jpg");
                        $url = getImagePath($path) ?? $defaultImage;
                        if (!isImageExists($url)) {
                            $url = $defaultImage;
                        }
                        $image = handleShowImageWithTypes($user->id ?? 0, $url, 40, 40);
                        $showUrl = url("admin/users/{$user->id}");

                        return "
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                $image
                                <div>
                                   <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                                     <span style='text-decoration: underline; cursor: pointer;'>{$user->name}</span>
                                    </a>
                                    <span style='color: #aaa; font-size: smaller;'>" . __('UUID:') . " {$user->uuid}</span>
                                </div>
                            </div>
                        ";
                    }
                    return __('User not found');

                case 'transfer_to_agency':
                    $agencyId = $data['agency_id'] ?? null;
                    $agency = \App\Models\Agency::find($agencyId);
                    if ($agency) {
                        $path = $agency->image ?? null;
                        $defaultImage = asset("images/businessman-icon.jpg");
                        $url = getImagePath($path) ?? $defaultImage;
                        $image = handleShowImageWithTypes($agency->id ?? 0, $url, 40, 40);
                        $agencyUrl = url("admin/agencies/{$agency->id}");
                        return "
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                $image
                                <div>
                                    <a href='{$agencyUrl}'>
                                        <span>" . __('Agency:') . " {$agency->name}</span>
                                    </a>
                                    <span>" . __('id:') . " {$agency->id}</span>
                                </div>
                            </div>
                        ";
                    }
                    return __('Agency not found');

                default:
                    return json_encode($data);
            }
        });

        $grid->column('created_at', __('Created at'))->display(function ($created_at) {
            return \Carbon\Carbon::parse($created_at)->format('Y-m-d H:i');
        });

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
        $show = new Show(Charge::find($id));


        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Charge);



        return $form;
    }
    public function transfer(Request $request)
    {
        // Dead-path guard: this method reads App\Models\BDSallary, a class that no
        // longer exists (its table bd_sallaries was dropped and it was superseded by
        // the UsersWallet system), so any real call throws a fatal "Class not found".
        // Re-wiring the correct balance source is a deferred product decision; until
        // then this is blocked cleanly at the entry point so it cannot crash.
        admin_toastr(__('This operation is currently unavailable.'), 'error');
        return back();

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $appID = Auth::user()->id;

        $netData = BDSallary::where('bd_id', $appID)
            ->selectRaw('SUM(sallary) as total_sallary, SUM(cut_amount) as total_cut')
            ->first();

        $finalSalary = ($netData->total_sallary ?? 0) - ($netData->total_cut ?? 0);

        if ($request->amount > $finalSalary) {
            admin_toastr(__('not_enough_balance'), 'error');
            return back();
        }

        $remaining = $request->amount;
        $salaries = BDSallary::where('bd_id', $appID)
            ->whereRaw('sallary > cut_amount')
            ->orderBy('id')
            ->get();

        foreach ($salaries as $salary) {
            $available = $salary->sallary - $salary->cut_amount;

            if ($available <= 0) {
                continue;
            }

            $cut = min($available, $remaining);
            $salary->cut_amount += $cut;
            $salary->save();

            $remaining -= $cut;

            if ($remaining <= 0) {
                break;
            }
        }

        WalletService::storeTransaction(
            $appID,
            'add',
            $request->amount,
            'user',
            'transfer_to_wallet',
            [],
            'trans_to_my_wallet'
        );

        admin_toastr(__('transferred_successfully'), 'success');
        return back();
    }


    public function charge(Request $request)
    {

        try {
            $request->validate([
                'amount' => 'required|integer|min:1',
                'target_id' => 'nullable',
                'target_type' => 'required|string',
            ]);

            $types = [
                'user' => [$this, 'chargeToUser'],
                'agency' => [$this, 'chargeToAgency']
            ];

            $type = $request->input('target_type');


            if (!array_key_exists($type, $types)) {
                admin_toastr('نوع الوجهة غير موجود', 'error');
                return back();
            }
            $data = call_user_func($types[$type], $request->all());
            admin_toastr('تم الشحن بنجاح', 'success');
            return back();
        } catch (\Exception $e) {
            admin_toastr($e->getMessage(), 'error');
            return back();
        } catch (\Throwable $e) {
            admin_toastr('حدث خطأ أثناء الشحن: ' . $e->getMessage(), 'error');
            return back();
        }
    }

    public function chargeToUser(array $data)
    {

        $bdId = Auth::user()->id;
        // $sender = Auth::user();
        $amount = $data['amount'];
        $receiverId = $data['target_id'] ?? null;


        if (\App\Helpers\Common::stopSwitch("bd_stop_charge")) {

            throw new \Exception(__('api_responses.freez_charge'));
        }

        // Scope guard (fail-closed): a BD may only charge a user whose agency belongs
        // to this BD (agencies.bd_id = Auth::id()). The actor is resolved from Auth,
        // never from the request; the target id is validated against that scope before
        // it is fetched, closing the raw-id IDOR.
        $inScope = User::where('id', $receiverId)
            ->whereIn('agency_id', function ($q) use ($bdId) {
                $q->select('id')->from('agencies')->where('bd_id', $bdId);
            })
            ->exists();

        if (!$inScope) {
            throw new \Exception(__('this user not found'));
        }

        $receiver = User::find($receiverId);
        $sender = Bd::find($bdId);
        if (!$receiver) {
            throw new \Exception(__('this user not found'));
        }
        if ($sender->transfer_salary == 1) {
            throw new \Exception(__('api_responses.freeze_transfer_charger'));
        }
        if ($receiver->transfer_salary == 1) {
            throw new \Exception(__('api_responses.freeze_transfer_receiver'));
        }
        $totalSalary = $sender?->bdSalary ?? 0;
        if ($totalSalary < $amount) {
            throw new \Exception(__('balance not enough'));
        }
        $rate = Common::getCoinsValue('user_coins');
        if (!$rate) {
            throw new \Exception(__('please set usd_value_in_coins in configs'));
        }

        $coins = $amount * $rate;

        return $this->startTransaction($receiver, $sender, $amount, $coins, 'user');
    }

    public function startTransaction(User $receiver, Bd $sender, int $amount, int $coins, string $receiverType)
    {
        return DB::transaction(function () use ($receiver, $sender, $amount, $coins, $receiverType) {
            // Lock the BD's salary rows FIRST (fixed ascending id order) and read the
            // available balance from that same locked read, so the sufficiency check
            // below cannot be raced by a concurrent charge/transfer draining the salary.
            $salaries = BdSalary::where('bd_id', $sender->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = '0';
            foreach ($salaries as $salary) {
                $available = bcadd($available, bcsub((string) $salary->salary, (string) $salary->cut_amount, 4), 4);
            }

            if (bccomp($available, (string) $amount, 4) < 0) {
                throw new \Exception(__('balance not enough'));
            }

            // Distribute the cut across the salary rows to match the summed sufficiency
            // check (the old incrementCutAmountInBdSallary applied it to the last row
            // only, which could push a single row's cut_amount past its salary).
            $remaining = (string) $amount;
            foreach ($salaries as $salary) {
                if (bccomp($remaining, '0', 4) <= 0) {
                    break;
                }
                $rowAvailable = bcsub((string) $salary->salary, (string) $salary->cut_amount, 4);
                if (bccomp($rowAvailable, '0', 4) <= 0) {
                    continue;
                }
                $cut = bccomp($rowAvailable, $remaining, 4) < 0 ? $rowAvailable : $remaining;
                $salary->cut_amount = bcadd((string) $salary->cut_amount, $cut, 4);
                $salary->save();
                $remaining = bcsub($remaining, $cut, 4);
            }

            // Lock the receiver's coin balance, then read/credit from the locked row.
            $amountBefore = (int) DB::table('users')->where('id', $receiver->id)->lockForUpdate()->value('di');

            UserCoinLogHelper::logByType(
                $receiver->id,
                $coins,
                $amountBefore,
                UserCoinLogType::BD_CHARGES,
            );

            DB::table('users')->where('id', $receiver->id)->increment('di', $coins);

            $descriptionData = ['receiver_id'  => $receiver->id];

            // Deduct from BD's linked user wallet
            $bdUserId = $sender->app_id ?? $sender->id;
            WalletService::storeTransaction(
                $bdUserId,
                'cut',
                $amount,
                'user',
                'transfer_to_user',
                $descriptionData,
                'charge'
            );

            $data = [
                'charger_id' => $sender->id,
                'charger_type' => 'bd',
                'user_id' => $receiver->id,
                'agency_id' => null,
                'user_type' => $receiverType,
                'amount' => $coins,
                'amount_type' => 2,
                "usd" =>  $amount ?? 0,
                'is_used_transferred' => 1,
                'user_charger_type' => 'bd'
            ];

            $charge =  Charge::create($data);

            UserCommon::UserEarnedInvitation($receiver->id, $coins, $charge->id);

            return true;
        });
    }

    public function chargeToAgency(array $data)
    {
        // Hierarchy guard (fail-closed, no money move): funding a shipping agency
        // with coins is handled EXCLUSIVELY by the shipping super admin layer
        // (ShippingSuperAdminWalletService). BD -> shipping agency is a direct jump
        // that skips that layer, so it is blocked here at the entry point before any
        // balance is read or written. This is a guard, not a deletion — reverting is
        // a one-line removal.
        throw new \Exception(__('Charging a shipping agency is handled exclusively by the shipping super admin layer.'));

        $user = Auth::user();
        $from = Bd::find($user->id);
        $usd = $data['amount'] ?? null;
        $toId = $data['target_id'] ?? null;

        if (\App\Helpers\Common::stopSwitch("stop_charge")) {

            throw new \Exception(__('api_responses.freez_charge'));
        }


        if (\App\Helpers\Common::stopSwitch("bd_stop_charge")) {

            throw new \Exception(__('api_responses.freez_charge'));
        }


        if ($from->transfer_salary == 1) {
            throw new \Exception(__('api_responses.freeze_transfer_charger'));
        }

        if (!is_numeric($usd) || $usd <= 0) {
            throw new \Exception(__('This value is not allowed'));
        }

        $to = ShippingAgency::find($toId);

        if (!$to || $to->is_frozen == 1) {
            throw new \Exception(__('it_agency_freez_charge'));
        }
        if (!ShippingAgencyHelper::isVerifiedChargeForAgency($to)) {
            throw new \Exception(__('not_verified_agency'));

        }

        $rate = Common::getCoinsValue('shipping_coins');
        if (!$rate) {
            throw new \Exception(__('api_responses.please set usd_value_in_coins in configs'));
        }
        // agencies.coins is an integer column; floor the rate product so any
        // fractional shipping_coins rate can only ever round toward the platform.
        $coins = (int) floor($usd * $rate);
        // $wallet = UserWallet::where('user_id', $from->id)->first();
        // if (!$wallet || ($wallet->value - $wallet->cut_amount) < $usd) {
        //     throw new \Exception(__('balance not enough'));
        // }    $totalSalary = $sender->salary;
        $totalSalary = $from->bdSalary;

        if ($totalSalary < $usd) {
            throw new \Exception(__('balance not enough'));
        }


        $this->performAgencyCharge($from, $to, $coins, $usd);
        return 1;
    }

    private function performAgencyCharge(Bd $fromUser, ShippingAgency $toAgency, $coins, $usd)
    {


        $fromUser->incrementCutAmountInBdSallary($usd);
        $toAgency->increment('coins', $coins);

        // Deduct from BD's linked user wallet
        $bdUserId = $fromUser->app_id ?? $fromUser->id;
        WalletService::storeTransaction(
            $bdUserId,
            'cut',
            $usd,
            'user',
            'transfer_to_agency',
            ['agency_id' => $toAgency->id],
            'charge'
        );

        $data = [
            'charger_id' => $fromUser->id,
            'charger_type' => 'bd',
            'user_id' => $toAgency->id,
            'agency_id' => null,
            'user_type' => 'agency',
            'amount' => $coins,
            'amount_type' => 2,
            'usd' => $usd,
            'is_used_transferred' => false,
            'user_charger_type' => 'bd'

        ];

        Charge::create($data);

        return true;
    }
}
