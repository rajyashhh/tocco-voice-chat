<?php

namespace App\Http\Controllers\Dashboard\Countries;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;

class AdminCountriesController extends Controller
{
    use DashBoardTrait;
    public function index()
    {
        $data = Country::orderBy('id','desc')->get();
        return $data;
    }

    public function country_autocomplete(Request $request)
    {
        $query = $request->get('query');
        $items = Country::where('name', 'like', '%'.$query.'%')->select('id','name','flag')->limit(10)->get();
        return response()->json($items);
    }

    public function enable_country( $id , $status)
    {
        $Country = Country::find($id);
        if($Country)
        {
            $check =  $status  == 'true' ? 1 : 0;
            $Country->status =   $check;
            $Country->save() ;
        }
        return response()->json([
            'status' => 200,
            'status2' => $status,
            'id' => $id,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'img'        => 'required|image|mimes:png,jpg',
            'name_en'       => 'required|max:255',
            'name_ar'        => 'required|max:255',
            'code'        => 'required|max:255',
        ]);

        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        Country::insert([
            'e_name'        => $request->name_en ,
            'name'          => $request->name_ar ,
            'phone_code'    => $request->code ,
            'flag'          => $img,
            'status'        => $request->enable ,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = Country::find($id);
        return $data;
    }

    public function update(Request $request, string $id)
    {
        $data = Country::find($id);
        $request->validate([
            'name_en'       => 'required|max:255',
            'name_ar'        => 'required|max:255',
            'code'        => 'required|max:255',
        ]);
        if( $request->hasFile('img'))
        {
            $this->delete_img($data->flag);
            $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;;
            $data->flag   = $img ;
        }
        $data->e_name         = $request->name_en ;
        $data->name           = $request->name_ar ;
        $data->phone_code     = $request->code ;
        $data->status         = $request->enable ;
        $data->update();
        return 200;

    }

    public function destroy(string $id)
    {
        $data = Country::find($id);
        if( $data->flag)
        {
            $this->delete_img($data->flag);
        }
        $data->delete();
        return 200;
    }
}
