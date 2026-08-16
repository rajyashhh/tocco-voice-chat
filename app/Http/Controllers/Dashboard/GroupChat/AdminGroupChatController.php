<?php

namespace App\Http\Controllers\Dashboard\GroupChat;

use App\Http\Controllers\Controller;
use  App\Http\Resources\Dashboard\GroupChat\GroupChatResource;
use App\Models\GroupChat;
use Illuminate\Http\Request;

class AdminGroupChatController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('query');
        $check = json_decode($query);
        if( $check->id !== '')
        {
          if($check->type == 'user_id')
            {
              $data = GroupChat::where('user_id',$check->id)->orderBy('id','desc')->with('user')->paginate(10);
            }
            else{
                $data = GroupChat::where('text', 'like', '%'.$check->id.'%')->orderBy('id','desc')->with('user')->paginate(10);
          }
        }
        else{
            $data = GroupChat::orderBy('id','desc')->with('user')->paginate(10);
        }
        return GroupChatResource::collection($data);
    }


    public function destroy(string $id)
    {
        $GroupChat = GroupChat::find($id);
        $GroupChat->delete();
        return 200;
    }
}
