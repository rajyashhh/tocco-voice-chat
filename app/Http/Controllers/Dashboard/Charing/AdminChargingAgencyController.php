<?php

namespace App\Http\Controllers\Dashboard\Charing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Users\ChargerUsersResource;
use App\Http\Resources\Dashboard\Users\UsersResource;
use App\Models\User;
use Illuminate\Http\Request;

class AdminChargingAgencyController extends Controller
{

    public function index()
    {
       $data = User::whereIn('type_user', [3 , 4])->get();
       return ChargerUsersResource::collection($data);
    }

    public function enable_user(Request $request, $id , $status)
    {
        $User = User::find($id);
        if($User)
        {
            $User->appear_charger_agency = $status  == 'true' ? 1 : 0;
            $User->update() ;
        }
        return response()->json([
            'status' => 200,
            'Ware' => $User,
        ]);
    }

}
