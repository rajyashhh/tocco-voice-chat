<?php

namespace App\Http\Controllers\Dashboard\Families;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Families\AdminFamiliesResource;
use App\Models\Family;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminFamiliesController extends Controller
{
    use DashBoardTrait;
    public function index()
    {
        $data = Family::orderBy('id','desc')->get();
        return AdminFamiliesResource::collection( $data);
    }

    public function enable_families(Request $request, $id , $status)
    {
        $Family = Family::find($id);
        if($Family)
        {
            $Family->status = $status  == 'true' ? 1 : 0;
            $Family->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }


    public function show(string $id)
    {
        $data = Family::find($id);
        return new AdminFamiliesResource( $data);
    }


    public function update(Request $request, string $id)
    {
        $Family = Family::find($id);
        $request->validate([
            'name'       => 'required|max:255',
            'status'    => 'required|max:255',
            'num'       => 'required|max:255',
            'status'       => 'required|max:255',
            'user_id'   => 'required|exists:users,id',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($Family->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'families') : null;;
            $Family->img   = $img ;
        }
        $Family->name      = $request->name ;
        $Family->status    = $request->status ;
        $Family->num       = $request->num ;
        $Family->user_id   = $request->user_id ;
        $Family->introduce = $request->introduce ;
        $Family->notice    = $request->notice ;
        $Family->update();
        return 200;
    }


    public function destroy(string $id)
    {
        //
    }
}
