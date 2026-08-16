<?php

namespace Modules\Region\Actions;

use App\Models\User;
use App\Models\Admin;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Facades\UserHandling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class DeleteAgencyAction extends RowAction
{
    public $name;



    // User::where('agency_id', $model->id)->update([
    //     'agency_id' => 0,
    //     'type_user' => 0,
    // ]);

    // // Delete the agency
    // $model->delete();


    public function __construct($id = 0)
    {
        $this->name = __("dashboard.delete");
        parent::__construct();
    }

    /**
     * Row actions run through the vendor _handle_action_ endpoint, which bypasses
     * the controller destroy/update guards and trusts the client-supplied _key.
     * Scope the target agency to the authenticated admin so a manager/BD cannot
     * delete an agency outside their tenancy by forging _key.
     */
    public function authorize($user, $model): bool
    {
        if (!$user || !$model) {
            return false;
        }

        if ($user->isAdministrator() || $user->can('*')) {
            return true;
        }

        if (in_array($user->type, ['region', 'sub_region'], true)) {
            return in_array((int) $model->country_id, array_map('intval', Common::areaCountries()), true);
        }

        if ($user->type === 'bd') {
            return (int) $model->bd_id === (int) Auth::id();
        }

        return false;
    }
    public function handle(Model $model, Request $request)
    {
        try {
            DB::beginTransaction();
            UserHandling::kickOfAllUsersFromAgency($model);
            // User::where('agency_id', $model->id)->update([
            //     'agency_id' => 0,
            //     'type_user' => 0,
            // ]);
            $user = User::find($model->app_owner_id);
            Admin::where('username', $user->uuid)->delete();
            $model->delete();
            DB::commit();
            return $this->response()->success(__('dashboard.successful'))->refresh();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->response()->error($exception->getMessage())->refresh();
        }
    }

    public function dialog()
    {
        $this->confirm(__('dashboard.chickDelete'), '', []);
    }
}
