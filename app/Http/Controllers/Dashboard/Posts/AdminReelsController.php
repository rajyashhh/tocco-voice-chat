<?php

namespace App\Http\Controllers\Dashboard\Posts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Posts\AdminReelsResource;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Modules\Reals\Entities\Real;

class AdminReelsController extends Controller
{
    use DashBoardTrait;

    public function index(Request $request)
    {
        $query = $request->get('query');
        $check = json_decode($query);
        if($check && $check->id !== '')
        {
          if($check->type == 'user_id')
            {
              $data = Real::where('user_id',$check->id)->orderBy('id','desc')->with('comments','likes')->paginate(10);
            }
            else{
                $data = Real::where('description', 'like', '%'.$check->id.'%')->orderBy('id','desc')->with('comments','likes')->paginate(10);
          }
        }
        else{
            $data = Real::orderBy('id','desc')->with('comments','likes')->paginate(10);
        }
        return AdminReelsResource::collection($data);
    }

    public function destroy(string $id)
    {
        $Real = Real::find($id);
        if(!$Real)
        {
            return 500;
        }
        if( $Real->url)
        {
            $this->delete_img($Real->url);
        }
        $Real->delete();
        return 200;
    }
}
