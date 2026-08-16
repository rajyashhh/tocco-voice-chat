<?php

namespace App\Http\Controllers\Dashboard\Vips;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Vips\AdminPrevilageVipsResource;
use Modules\Vip\Entities\VipPrivilege;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminPrivilegeVipsController extends Controller
{
    use DashBoardTrait;
    public function index()
    {
        $data = VipPrivilege::orderBy('id','desc')->get();
        return AdminPrevilageVipsResource::collection($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_en'     => 'required|max:255',
            'name_ar'     => 'required|max:255',
            'title'    => 'nullable|max:255',
            'type_id'     => 'required|max:255',
            'img1'    => 'required|image|mimes:jpeg,png,jpg',
            'img2'        => 'required|image|mimes:jpeg,png,jpg',
        ]);

        $img1_name = $request->hasFile('img1') ? $this->store_img($request->file('img1'), 'images') : null;;
        $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'files') : null;
        VipPrivilege::insert([
            'en_name'     => $request->name_en,
            'name'     => $request->name_ar,
            'title'    => $request->title ?? null,
            'type'     => $request->type_id,
            'img1'    => $img1_name ,
            'img2'        => $img2_name,
        ]);

        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = VipPrivilege::find($id);
        return new  AdminPrevilageVipsResource($data);
    }

    public function update(Request $request, string $id)
    {
        $Privilege = VipPrivilege::find($id);
        $request->validate([
            'name_en'     => 'required|max:255',
            'name_ar'     => 'required|max:255',
            'title'    => 'nullable|max:255',
            'type_id'     => 'required|max:255',
        ]);
        if( $request->hasFile('img1'))
        {
            $this->delete_img($Privilege->img1);
            $img1_name = $request->hasFile('img1') ? $this->store_img($request->file('img1'), 'images') : null;;
            $Privilege->img1   = $img1_name ;
        }
        if( $request->hasFile('img2'))
        {
            $this->delete_img($Privilege->img2);
            $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'files') : null;
            $Privilege->img2       = $img2_name;
        }

        $Privilege->en_name    = $request->name_en;
        $Privilege->name    = $request->name_ar;
        $Privilege->title   = $request->title ?? null;
        $Privilege->type    = $request->type_id;
        $Privilege->update();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function destroy(string $id)
    {
        $Privilege = VipPrivilege::find($id);
        $this->delete_img($Privilege->img1 ?? null);
        $this->delete_img($Privilege->img2 ?? null);
        $Privilege->delete();
        return 200;
    }
}
