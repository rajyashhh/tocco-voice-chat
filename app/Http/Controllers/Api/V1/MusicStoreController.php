<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MusicResource;
use App\Models\MusicStore;
use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\DashBoardTrait;


class MusicStoreController extends Controller
{
    use DashBoardTrait;
    public function index()
    {
        $data = MusicStore::paginate(10);
    //   return $data;
        return Common::apiResponse(1, 'successfully',MusicResource::collection($data));
    }
    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|mimes:mp3,wav,ogg|max:20480', 
        ]);
    
        $music_name = $this->store_music($request->file('file'), 'music');
        
        if (!is_null($music_name)) {  
            $music = MusicStore::create(
                [
                    'name' => $request->name,
                    'url' => $music_name,
                ]
            );
            
            return Common::apiResponse(1, 'created successfully');

        } else {
            return Common::apiResponse(0, 'false', null, 400);
        }
        
    }

   
}
