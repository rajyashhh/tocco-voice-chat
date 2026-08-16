<?php

namespace App\Http\Controllers\Dashboard\Wallet;

use App\Http\Controllers\Controller;
use App\Models\CoreWallet;
use Illuminate\Http\Request;

class AdminCoreWalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = CoreWallet::orderBy('id','desc')->get();
        return $data;
    }


    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|max:255',
            'coins'        => 'required|max:255',
        ]);
        CoreWallet::insert([
            'name'         => $request->name ,
            'coins'          => $request->coins ,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = CoreWallet::find($id);
        return $data;
    }


    public function update(Request $request, string $id)
    {
        $CoreWallet = CoreWallet::find($id);
        $request->validate([
            'name'       => 'required|max:255',
            'coins'        => 'required|max:255',
        ]);
        $CoreWallet->name       = $request->name ;
        $CoreWallet->coins    = $request->coins ;
        $CoreWallet->update();
        return 200;
    }


    public function destroy(string $id)
    {
        $CoreWallet = CoreWallet::find($id);
        $CoreWallet->delete();
        return 200;
    }
}
