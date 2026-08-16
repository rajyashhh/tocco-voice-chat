<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Enums\Charges\UserTypeEnum;
use App\Enums\UserCoinLogType;
use App\Helpers\ShippingAgencyHelper;
use App\Helpers\UserCoinLogHelper;
use App\Models\Setting;
use Modules\Country\Entities\SubAdmin;
use Modules\Country\Entities\SuperAdmin;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\BDSallary;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Models\ShippingAgency;
use App\Services\WalletService;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Illuminate\Validation\ValidationException;
use App\Models\ChargeInvoice;



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
                $row->column(12, view('admin.grid.superadmin.wallet', ['finalSalary' => $finalvalue]));
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
        $grid->model()->with(['user.profile'])->where('user_id', $currentUserId);

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
        // FIX-4 (BLOCKED — financial intent unresolved; no behavior change applied):
        // This method (and its BD + AreaManager twins) references App\Models\BDSallary,
        // which was DELETED (commit 77cda71d99 "rm old") together with its table
        // `bd_sallaries` (dropped in Modules/UsersWallet migration 2025_12_07_090852),
        // superseded by the new UsersWallet system. So the line below throws a fatal
        // "Class not found" — the feature is fully broken, not merely returning ~0.
        // The correct balance source for a country manager (superadmin) is ALSO unclear:
        // index() above reads UserWallet, while a superadmin is not a BD so BdSalary keyed
        // by bd_id would not match either. Rewiring this moves real money, so per the
        // money-review policy it must not be guessed. Requires a product decision before
        // any rewrite + DB::transaction/lockForUpdate/atomic-decrement hardening.
        //
        // Dead-path guard: blocked cleanly at the entry point so the missing
        // BDSallary class cannot throw a fatal at runtime.
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
            'user_transaction',
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
                'charge_type' => 'required|in:dollar,coins',
            ]);

            $types = [
                'user' => [$this, 'chargeToSubAdmin'],
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

    public function chargeToAgency(array $data)
    {
        // Hierarchy guard (fail-closed, no money move): funding a shipping agency
        // with coins is handled EXCLUSIVELY by the shipping super admin layer
        // (ShippingSuperAdminWalletService). Country manager -> shipping agency is a
        // direct jump that skips that layer, so it is blocked here at the entry point
        // before any balance is read or written. Guard, not deletion.
        throw new \Exception(__('Charging a shipping agency is handled exclusively by the shipping super admin layer.'));

        $user = Auth::user();
        $from = SuperAdmin::find($user->id);
        $amount = $data['amount'] ?? null;
        $toId = $data['target_id'] ?? null;

        // if (settings()->get("stop_charge", 0)) {

        //     throw new \Exception(__('api_responses.freez_charge'));
        // }
        if ($user->is_frozen_wallet) throw new \Exception(__('your wallet frozen.'));

        if ($from->transfer_salary == 1) {
            throw new \Exception(__('api_responses.freeze_transfer_charger'));
        }

        if (!is_numeric($amount) || $amount <= 0) {
            throw new \Exception(__('This value is not allowed'));
        }

        $to = ShippingAgency::find($toId);

        if (!$to) {
            throw new \Exception(__('This agency not found'));
        }

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

        //        $coins = $amount * $rate;

        $chargeType = $data['charge_type'];

        if ($chargeType === 'dollar') {
            $usdAmount = $amount;
            $coinAmount = $amount * $rate;
        } else {
            $coinAmount = $amount;
            $usdAmount = $amount / $rate;
        }

        $totalSalary = $from->di;

        if ($totalSalary < $coinAmount) {
            throw new \Exception(__('balance not enough'));
        }

        $this->performAgencyCharge($from, $to, $coinAmount, $usdAmount);
        return 1;
    }

    private function performAgencyCharge(SuperAdmin $fromUser, ShippingAgency $toAgency, $coins, $usd)
    {
        $fromUser->decrement('di', $coins);
        $toAgency->increment('coins', $coins);
        $adminType = null;
        if (auth('admin')->user()->type === 'sub_country') {
            $adminType = UserTypeEnum::SUB_ADMIN;
        }

        if (auth('admin')->user()->type === 'country') {
            $adminType = UserTypeEnum::SUPER_ADMIN;
        }

        WalletService::storeTransaction(
            $fromUser->id,
            'cut',
            $usd,
            'user_transaction',
            'transfer_to_agency',
            ['agency_id' => $toAgency->id],
            'trans_to_agency'

        );

        $data = [
            'charger_id' => $fromUser->id,
            'charger_type' => $adminType,
            'user_id' => $toAgency->id,
            'agency_id' => null,
            'user_type' => 'agency',
            'amount' => $coins,
            'amount_type' => 2,
            'usd' => $usd,
            'is_used_transferred' => false,
            'user_charger_type' => $adminType

        ];

        Charge::create($data);

        return true;
    }




    public function chargeToSubAdmin(array $data)
    {
        $user = Auth::user();
        $amount = $data['amount'] ?? 0;
        $toId = $data['target_id'] ?? null;

        if (!$amount || !$toId) {
            throw new \Exception(__('Invalid request data.'));
        }

        if ($user->is_frozen_wallet) throw new \Exception(__('your wallet frozen.'));

        $subAdmin = SubAdmin::where('parent_id', $user->id)->find($toId);

        if (!$subAdmin) {
            throw new \Exception(__('This sub admin not found under your account.'));
        }

        $userCoins = \Cache::rememberForever('super_admin_coins', function () {
            return Setting::where('key', 'super_admin_coins')->value('value') ?? 1;
        });

        //        $coins = $amount * $userCoins;

        $chargeType = $data['charge_type'];

        if ($chargeType === 'dollar') {
            $usdAmount = $amount;
            $coinAmount = $amount * $userCoins;
        } else {
            $coinAmount = $amount;
            $usdAmount = $amount / $userCoins;
        }


        return DB::transaction(function () use ($user, $subAdmin, $coinAmount, $usdAmount, $data) {
            // Both balances live on admin_users.di. Lock in ascending id order and
            // read di from the same locked read, so the sufficiency check cannot be
            // raced by a concurrent charge draining the sender.
            $ids = [$user->id, $subAdmin->id];
            sort($ids);
            $locked = [];
            foreach ($ids as $id) {
                $locked[$id] = (int) DB::table('admin_users')->where('id', $id)->lockForUpdate()->value('di');
            }

            if ($locked[$user->id] < $coinAmount) {
                throw new \Exception(__('Insufficient balance.'));
            }

            DB::table('admin_users')->where('id', $user->id)->decrement('di', $coinAmount);
            DB::table('admin_users')->where('id', $subAdmin->id)->increment('di', $coinAmount);

            $subAdmin->di = $locked[$subAdmin->id] + $coinAmount;

            $this->createChargeRecord($data, $subAdmin, $coinAmount, $usdAmount);

            return true;
        });
    }




    private function createChargeRecord($request, SubAdmin $subAdmin, $coins = 0, $usdAmount)
    {

        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type =  UserTypeEnum::SUPER_ADMIN;
        $charge->user_id = $subAdmin->id;
        $charge->agency_id =   null;
        $charge->user_type = UserTypeEnum::SUB_ADMIN;
        $charge->amount = $coins;
        $charge->usd = $usdAmount;
        $charge->balance_before =  $subAdmin->di  - $coins;
        $charge->save();




        return  true;
    }
}
