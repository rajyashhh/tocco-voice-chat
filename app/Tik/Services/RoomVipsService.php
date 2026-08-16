<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use App\Tik\Repositories\ReelsRepository;
use App\Tik\Repositories\RoomVipsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class RoomVipsService 
{

    public function __construct(
        private readonly RoomVipsRepository $roomVipsRepository,

    ) {}

    public function index($perPage, $Page,)
    {
        return $this->roomVipsRepository->all($perPage, $Page);
    }

   

    public function show($id)
    {

        return $this->roomVipsRepository->find($id);
    }

    public function store(Request $request)
    {

        if ($request->hasFile('img')) {
            $image= Common::upload('RoomVips', $request->file('img'));
        }
    

        $roomVipData = [
            'type' => 4,
            'img' => $image ??'',
            'exp' => $request->exp,
            'level' => $request->level,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
        ];

        $FamilyLevel =  $this->roomVipsRepository->store($roomVipData);

        return $FamilyLevel;

    }

  
    public function search($key)
    {

        return $this->roomVipsRepository->search($key);
    }
    
   
    public function delete( $reelId)
    {
        $reel = $this->roomVipsRepository->find($reelId);
        if (!$reel) throw new \Exception('not found');
        // if (!$reel)  return Common::apiResponse(false, 'not found', null);

        $reel->delete();
        return true;
    }

   

   
   
}
