<?php

namespace App\Admin\Actions;


use App\Models\AgencyUserJob;
use App\Models\GiftLog;
use App\Models\User;
use App\Models\Agency;
use App\Models\UserSallary;
use App\Helpers\Common;
use Encore\Admin\Admin;
use Encore\Admin\Facades\Admin as AdminFacade;
use Illuminate\Http\Request;
use App\Models\UsersJoinedAgency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class ChangeUsersAgencyAction extends RowAction
{
    public $name;

    public $id;

    public function __construct($id = 0)
    {
        Admin::script('$.fn.modal.Constructor.prototype.enforceFocus = function () {};');

        $this->id = $id;
        $this->name = __("dashboard.changeMemberAgency");
        parent::__construct();
    }

    /**
     * The action runs through the vendor _handle_action_ endpoint, bypassing the
     * controller guards and trusting client-supplied ids. $model is the grid row
     * (the source/old agency); the destination is validated again in handle().
     * Super admins are unrestricted so the shared main-admin panel is unaffected.
     */
    public function authorize($user, $model): bool
    {
        if (!$user || !$model) {
            return false;
        }

        if ($user->isAdministrator() || $user->can('*')) {
            return true;
        }

        return $this->agencyInScope($user, $model);
    }

    /** Whether the given agency falls within the authenticated admin's tenancy. */
    private function agencyInScope($user, ?Agency $agency): bool
    {
        if (!$agency) {
            return false;
        }

        if (in_array($user->type, ['region', 'sub_region'], true)) {
            return in_array((int) $agency->country_id, array_map('intval', Common::areaCountries()), true);
        }

        if ($user->type === 'bd') {
            return (int) $agency->bd_id === (int) Auth::id();
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    public function handle(Model $model, Request $request)
    {
        $oldAgencyId = $request->old_agency_id;
        $newAgencyId = $request->new_agency_id;

        // Destination scope enforcement: authorize() only sees the source row, so
        // re-check both endpoints here for non-super admins before moving members.
        $user = AdminFacade::user();
        if ($user && !($user->isAdministrator() || $user->can('*'))) {
            $oldAgency = Agency::find($oldAgencyId);
            $newAgency = Agency::find($newAgencyId);
            if (!$this->agencyInScope($user, $oldAgency) || !$this->agencyInScope($user, $newAgency)) {
                throw new \Exception(__('admin.deny'));
            }
        }

        $ownerId = Agency::where('id', $oldAgencyId)->value('app_owner_id');

        $users = User::where('agency_id', $oldAgencyId)
            ->where('id', '!=', $ownerId)
            ->get();

        if ($users->isEmpty()) {
            $error = new MessageBag([
                'title' => __('error_title_div'),
                'message' => __('cant it owner or no members to move'),
            ]);
            session()->flash('error', $error);
            throw new \Exception(__('cant it owner or no members to move'));
        }

        UsersJoinedAgency::where('agency_id', $oldAgencyId)
            ->whereNull('leave_date')
            ->update([
                'leave_date' => now(),
                'status' => 'change agency by admin'
            ]);

        foreach ($users as $user) {

            $this->handleUserSalaries($user);
            $this->clearUserAgencyLogs($user ,$oldAgencyId);
            $user->agency_id = $newAgencyId;
            $user->save();

            uploadMonthlyDiamondReceive($user->id, 0);

            $joined = UsersJoinedAgency::where([
                'user_id' => $user->id,
                'agency_id' => $newAgencyId
            ])->whereNull('leave_date')->first();

            if (!$joined) {
                UsersJoinedAgency::create([
                    'user_id' => $user->id,
                    'agency_id' => $newAgencyId,
                    'type' => 2,
                    'join_date' => now(),
                    'status' => 'Joined'
                ]);
            }
        }

        return $this->response()->success('success')->refresh();
    }

    /**
     * Handle user's monthly salaries
     */
    private function handleUserSalaries(User $user)
    {
   
        $agencyId = $user->agency_id;
        $timezone = getTimezone();
        $currentMonth = now( $timezone)->month;
        $currentYear = now( $timezone)->year;

        $userSalaries = UserSallary::query()
            ->where('user_id', $user->id)
            ->where('user_agency_id', $agencyId)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('is_finished', 0)
            ->first();
         
        if (!$userSalaries) return;
       
        if ($userSalaries->month == $currentMonth && $userSalaries->year == $currentYear) {
          
            $userSalaries->update(['is_finished' => 1]);
        }
        uploadMonthlyDiamondReceive($user->id, 0);

    
    }


    private function clearUserAgencyLogs(User $user,$agencyId)
    {
        GiftLog::query()->where('receiver_id', $user->id)
        ->where('agency_id', $agencyId)->update(['is_finished' => 1]);
        AgencyUserJob::where(['user_id' => $user->id, 'agency_id' => $agencyId])->delete();
    }

    public function form()
    {
        $this->hidden('old_agency_id', __('id'))->value($this->id);
        $currentId = $this->id;
        $this->select('new_agency_id', __('agency id'))->options(function ($value) use ($currentId) {
            $agencies = Cache::remember('agencies_select_options', 300, function () {
                return Agency::query()
                    ->where(function ($query) {
                        $query->whereDoesntHave('additionalInfo')
                            ->orWhereHas('additionalInfo', fn($q) => $q->where('status', 1));
                    })
                    ->select('id', 'name')
                    ->get()
                    ->pluck('name', 'id')
                    ->mapWithKeys(fn($name, $id) => [$id => $id . '_' . $name])
                    ->toArray();
            });
            unset($agencies[$currentId]);
            return $agencies;
        });
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" ></a>
            <script>

            function pu(val) {

              $("#vid").val(val)
            }
            </script>
            ';
    }
}
