<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgenciesRequestsResource;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\AgencyApp\Entities\AdditionalInfo;
use Illuminate\Support\Facades\Notification;
use App\Facades\CustomNotification;
use App\Notifications\AcceptAgency;
use App\Notifications\RefuseAgency;

class RequestAgenciesController extends Controller
{
    public function index()
    {
        $id = request('id');
        $owner_uuid = request('owner_uuid');
        $perPage = request('per_page') ?? 10;

        $requests = Agency::with('additionalInfo', 'owner')->where('status', 0)
            ->when($id, function ($q) use ($id) {
                $q->where('id', $id);
            })
            ->when($owner_uuid, function ($q) use ($owner_uuid) {
                $q->whereHas('owner', function ($q2) use ($owner_uuid) {
                    $q2->where('uuid', $owner_uuid);
                });
            })
            ->whereHas('additionalInfo', function ($q) {
                $q->where('status', 0);
            })
            ->orderByDesc("id")
            ->paginate($perPage);

        return Common::apiResponse(true, 'Success', AgenciesRequestsResource::collection($requests));
    }


    public function update($id, Request $request)
    {
        $request->validate([
            'accept' => 'required'
        ]);

        if ($request->accept == 1) {
            return $this->accept_agency($id);
        }

        return $this->refuse_agency($id);
    }


    public function refuse_agency($id)
    {
        $agency = Agency::find($id);
        $user = User::find($agency->app_owner_id);
        $additionalInfo = AdditionalInfo::where('agency_id', $agency->id)->first();
        $additionalInfo->status = 2;
        $additionalInfo->save();
        if ($agency->additionalInfo->gmail) {
            Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new RefuseAgency());
        }
        $agency->delete();
        CustomNotification::refuseRequestAgency($user);
        return Common::apiResponse(true, 'Success');
    }
    public function accept_agency($id)
    {
        $agency = Agency::find($id);
        $user = User::find($agency->app_owner_id);
        $agency->status = 1;
        $agency->save();
        $additionalInfo = AdditionalInfo::where('agency_id', $agency->id)->first();
        $additionalInfo->status = 1;
        $additionalInfo->save();
        $appOwnerId = $agency->app_owner_id;
        $user = User::find($appOwnerId);

        \App\Facades\UserHandling::changeUserAgency($user, $agency->id, 2);

        if ($agency->additionalInfo->gmail) {
            Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new AcceptAgency());
        }
      //  Common::createUserAdmin($appOwnerId);
        CustomNotification::acceptRequestAgency($user);
        return Common::apiResponse(true, 'Succcess');
    }

}
