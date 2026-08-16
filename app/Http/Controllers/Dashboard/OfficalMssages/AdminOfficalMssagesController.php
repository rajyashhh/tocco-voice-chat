<?php

namespace App\Http\Controllers\Dashboard\OfficalMssages;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\OfficalMssages\AdminOfficalMssagesResource;
use App\Models\OfficialMessage;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminOfficalMssagesController extends Controller
{
    use DashBoardTrait;

    public function index(Request $request)
    {
        $data = OfficialMessage::orderBy('id','desc')->paginate(10);
        return AdminOfficalMssagesResource::collection($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'        => 'required',
            'url'       => 'required|max:255',
            'content'    => 'required|max:255',
            'title'     => 'required|max:255',
        ]);
        if($request->type == 'user')
        {
            $request->validate([
                'user_id'        => 'required|exists:users,id',
            ]);
        }
        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        $data = [
            'url'         => $request->url ,
            'img'          => $img,
            'content'      => $request->content ,
            'title'        => $request->title ,
            'type'         => 2 ,
        ];
        if ($request->type == 'user') {
            $data['user_id'] = $request->user_id;
        } else {
            $data['user_id'] = 0;
        }
        OfficialMessage::create($data);
        return 200;
    }

    public function show(string $id)
    {
        $data = OfficialMessage::find($id);
        return new AdminOfficalMssagesResource($data);
    }

    public function update(Request $request, string $id)
    {
        $OfficialMessage = OfficialMessage::find($id);
        $request->validate([
            'url'       => 'required|max:255',
            'content'    => 'required|max:255',
            'title'     => 'required|max:255',
        ]);
        if($request->type == 'user')
        {
            $request->validate([
                'user_id'        => 'required|exists:users,id',
            ]);
        }

        if( $request->hasFile('img'))
        {
            $this->delete_img($OfficialMessage->img);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $OfficialMessage->img   = $img ;
        }
        $OfficialMessage->url       = $request->url ;
        $OfficialMessage->content    = $request->content ;
        $OfficialMessage->title     = $request->title ;
        if ($request->type == 'user') {
            $OfficialMessage->user_id = $request->user_id;
        } else {
            $OfficialMessage->user_id = 0;
        }

        $OfficialMessage->update();
        return 200;
    }


    public function destroy(string $id)
    {
        $OfficialMessage = OfficialMessage::find($id);
        if( $OfficialMessage->img)
        {
            $this->delete_img($OfficialMessage->img);
        }
        $OfficialMessage->delete();
        return 200;
    }
}
