<?php

namespace App\Http\Controllers\Dashboard\Codes;

use App\Http\Controllers\Controller;
use App\Models\Code;
use Illuminate\Http\Request;

class AdminCodesController extends Controller
{

    public function index()
    {
        $data = Code::orderBy('id','desc')->get();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone'       => 'required|max:255',
            'code'        => 'required|max:255',
        ]);
        Code::insert([
            'phone'         => $request->phone ,
            'code'          => $request->code ,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Code::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Code = Code::find($id);
        $request->validate([
            'phone'       => 'required|max:255',
            'code'        => 'required|max:255',
        ]);
        $Code->phone       = $request->phone ;
        $Code->code    = $request->code ;
        $Code->update();
        return 200;
    }

    public function destroy(string $id)
    {
        $Code = Code::find($id);
        $Code->delete();
        return 200;
    }
}
