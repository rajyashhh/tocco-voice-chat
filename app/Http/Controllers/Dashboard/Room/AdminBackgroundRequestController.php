<?php

namespace App\Http\Controllers\Dashboard\Room;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Room\AdminBackgroundRequestsResource;
use App\Models\RequestBackgroundImage;
use Illuminate\Http\Request;

class AdminBackgroundRequestController extends Controller
{

    public function index()
    {
        $data = RequestBackgroundImage::orderBy('id','desc')->get();
        return AdminBackgroundRequestsResource::collection($data);
    }

    public function show(string $id)
    {
        $data = RequestBackgroundImage::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $item = RequestBackgroundImage::find($id);
        $item->status = $request->status;
        $item->update();
        return 200 ;
    }

    public function destroy(string $id)
    {
        $item = RequestBackgroundImage::find($id);
        $this->delete_img($item->img);
        $item->delete();
        return 200;
    }
}
