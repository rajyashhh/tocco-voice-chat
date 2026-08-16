<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use App\Tik\Repositories\MangerTypeRepository;



class MangerTypeService
{
    public function __construct(
        private readonly MangerTypeRepository $mangerTypeRepository
    ) {}


    public function index()
    {
        return $this->mangerTypeRepository->all();
    }

    public function create($request)
    {
        $image = null;
        if ($request->hasFile('image')) {
            $image = Common::upload('images', $request->file('image'));
        }
        $data = [

            'name_en'         => $request->name_en,
            'name_ar'         => $request->name_ar,
            'description_en'         => $request->description_en,
            'description_ar'         => $request->description_ar,
            'img'     => $image,
        ];

        $this->mangerTypeRepository->create($data);
        return true;
    }

    public function show($id)
    {
        return $this->mangerTypeRepository->findById($id);
    }

    public function update($request)
    {
        $data = [

            'name_en'         => $request->name_en,
            'name_ar'         => $request->name_ar,
            'description_en'         => $request->description_en,
            'description_ar'         => $request->description_ar,
        ];
        
        if ($request->hasFile('image')) {
            $data['img'] = Common::upload('images', $request->file('image'));
        }

        $this->mangerTypeRepository->update($data, $request->manger_type_id);
        return true;
    }
}
