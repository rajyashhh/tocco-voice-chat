<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\MangerTypesRepository;


class MangerTypesService
{
    public function __construct(private readonly MangerTypesRepository $MangerTypesRepository) {}

    public function all($id)
    {
        return $this->MangerTypesRepository->all($id);
    }

    public function create($request)
    {
        if ($request->hasFile('files')) {
            $img = Common::upload('images', $request->file('files'));
        }
        $data = [
    
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'description_ar' => $request->description_ar,
            'description_en' => $request->description_en,
            'img' =>  $img ?? ''
        ];
        $this->MangerTypesRepository->create($data);
        return true;
    }

    public function update($id, $request)
    {

        $data = [
    
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'description_ar' => $request->description_ar,
            'description_en' => $request->description_en,
            'img' =>  $img ?? ''
        ];
        if ($request->hasFile('files')) {
            $data['img'] = Common::upload('images', $request->file('files'));
        }
        $this->MangerTypesRepository->update($data, $id);
        return true;
    }

    public function delete($id)
    {

        $data = $this->MangerTypesRepository->findOrFail($id);
        $data->delete();
        return true;
    }

    public function show($id)
    {
        return $this->MangerTypesRepository->all($id);
    }

    public function search($id)
    {

        return $this->MangerTypesRepository->search($id);
    }
}
