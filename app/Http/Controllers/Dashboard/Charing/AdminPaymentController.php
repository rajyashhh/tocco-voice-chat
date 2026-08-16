<?php

namespace App\Http\Controllers\Dashboard\Charing;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = PaymentGateway::orderBy('id','desc')->get();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'    => 'required',
            'name'      => 'required|max:255',
        ]);
        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        PaymentGateway::insert([
            'photo'        => $img,
            'title'      => $request->name ,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = PaymentGateway::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $Item = PaymentGateway::find($id);
        $request->validate([
            'name'      => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($Item->photo);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $Item->photo   = $img ;
        }
        $Item->title         = $request->name ;
        $Item->update();
    }

    public function destroy(string $id)
    {
        $item = PaymentGateway::find($id);
        $this->delete_img($item->photo);
        $item->delete();
        return 200;
    }
}
