<?php

namespace App\Http\Controllers\Dashboard\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Users\SingleUserResource;
use App\Models\Admin;
use App\Models\AdminUser;
use Illuminate\Http\Request;

class ProfileDashboardController extends Controller
{
    function index(Request $request){
        $user = Admin::find($request->user()->id);
        return [
            'admin' =>$request->user(),
            'roles' =>$request->user()->allPermissions()->pluck('slug')->toArray()
        ];

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if($user->can("delete-rooms"))
        {
            //
        }

    }

    public function show(string $id)
    {
        $user = Admin::find($id);
        return new SingleUserResource($user);

    }

    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    function destroy ( Request $request , $id){
        $request->user()->tokens()->delete();
        return 'user logged out';
    }
}
