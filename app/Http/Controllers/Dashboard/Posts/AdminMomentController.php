<?php

namespace App\Http\Controllers\Dashboard\Posts;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Posts\AdminMomentResource;
use Illuminate\Http\Request;
use Modules\Moment\Entities\Moment;
use App\Traits\Dashboard\DashBoardTrait;

class AdminMomentController extends Controller
{
    use DashBoardTrait;

    public function index(Request $request)
    {
        $query = $request->get('query');
        $check = json_decode($query);
        if( $check->id !== '')
        {
          if($check->type == 'user_id')
            {
              $data = Moment::where('user_id',$check->id)->orderBy('id','desc')->with('comments','likes')->paginate(10);
            }
            else{
                $data = Moment::where('description', 'like', '%'.$check->id.'%')->orderBy('id','desc')->with('comments','likes')->paginate(10);
          }
        }
        else{
            $data = Moment::orderBy('id','desc')->with('comments','likes')->paginate(10);
        }
        return AdminMomentResource::collection($data);
    }

    public function destroy(string $id)
    {
        $Moment = Moment::find($id);
        if( $Moment->img)
        {
            $this->delete_img($Moment->img);
        }
        $Moment->delete();
        return 200;
    }
}
