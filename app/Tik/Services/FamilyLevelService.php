<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\FamilyLevelRepository;


class FamilyLevelService
{

    public function __construct(
        private readonly FamilyLevelRepository $FamilyLevelRepository,

    ) {}

    public function index()
    {
        return $this->FamilyLevelRepository->all();
    }

   

    public function show($id)
    {

        return $this->FamilyLevelRepository->find($id);
    }

    public function create( $request)
    {

        if ($request->hasFile('img')) {
            $image= Common::upload('FamilyLevels', $request->file('img'));
        }

   
        $FamilyLevelData = [
            'name' => $request->name,
            'img' => $image ??'',
            'exp' => $request->exp,
            // 'type' => $request->type,
            'members' => $request->members,
            'admins' => $request->admins,
        ];
        $FamilyLevel =  $this->FamilyLevelRepository->store($FamilyLevelData);

        return $FamilyLevel;
       
    }



    public function update( $request, $FamilyLevelId)
    {
        $FamilyLevel = $this->FamilyLevelRepository->find($FamilyLevelId);
        if (!$FamilyLevel) throw new \Exception('not found');
        if ($request->name) {
            $FamilyLevel->name = $request->name;
        }
        if ($request->exp) {
            $FamilyLevel->exp = $request->exp;
        }
        // if ($request->type) {
        //     $FamilyLevel->type = $request->type;
        // }
        if ($request->members) {
            $FamilyLevel->members = $request->members;
        }
        if ($request->admins) {
            $FamilyLevel->admins = $request->admins;
        }
        if ($request->hasFile('img')) {
            $FamilyLevel->img = Common::upload('families', $request->file('img'));
        }
        $FamilyLevel->save();
        return $FamilyLevel;
    }

   

    public function delete( $FamilyLevelId)
    {
        $FamilyLevel = $this->FamilyLevelRepository->find($FamilyLevelId);
        if (!$FamilyLevel) throw new \Exception('not found');
        $FamilyLevel->delete();
        return true;
    }

   

   
   
}
