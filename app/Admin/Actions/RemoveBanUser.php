<?php

namespace App\Admin\Actions;

use App\Helpers\Common;
use Encore\Admin\Facades\Admin;
use App\Models\Agency;
use App\Models\Ban;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\User;
use Encore\Admin\Actions\Action;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Facades\CustomNotification;
use Encore\Admin\Auth\Permission;

class RemoveBanUser extends Action
{
    public $name;

    protected $selector = '.remove_ban_user_action';
    public $permission_name = 'bans';

    public function handle(Request $request)
    {
        $countryID = Common::filterCountryIds();


        if (!Admin::user()->can('*')) {
            Permission::check('delete-' . $this->permission_name);
        }

        $user = User::query()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->searchByUuid($request->uid)->first();
        if (!$user) {
            return $this->response()->error(__('user not found'))->refresh();
        }
        $userUuid  = $user->original_uuid;
        Ban::query()->where('uid',  $userUuid)->delete();
        \App\Services\BanGuard::invalidate($userUuid);
        CustomNotification::removeBanUser($user);
        return $this->response()->success('success')->refresh();
    }

    public function form()
    {
        $this->text('uid', __('uuid'));
    }

    public function html()
    {
        $removeBans = __('dashboard.remove_bans'); // Fetch translation

        return <<<HTML

        <a href="javascript:void(0);" class="remove_ban_user_action btn btn-sm  text-white"
       style="background-color: var(--primary-color); border-color: var(--secondary-color); color: var(--text-secondary-color);">
            {$removeBans}
        </a>
    HTML;
    }
}
