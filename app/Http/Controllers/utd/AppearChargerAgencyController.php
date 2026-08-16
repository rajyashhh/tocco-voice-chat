<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppearChargerAgencyResource;
use App\Models\User;
use Illuminate\Http\Request;

class AppearChargerAgencyController extends Controller
{

    public function index()
    {
        $id = request('id');
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $users = User::when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'LIKE', "%$search%")
                        ->orWhere('uuid', 'LIKE', "%$search%")
                        ->orWhere('phone', 'LIKE', "%$search%");
                });
            })
            ->whereIn('type_user', [3, 4])
            ->paginate($perPage);


            return Common::apiResponse(true,'Success', AppearChargerAgencyResource::collection($users));
    }
    public function update(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $user->update([
            'appear_charger_agency' => $request->appear_charger_agency
        ]);

        return Common::apiResponse(true, 'Success');
    }
}
