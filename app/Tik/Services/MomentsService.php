<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\MomentsRepository;


class MomentsService
{
    public function __construct(private readonly MomentsRepository $MomentsRepository) {}

    public function all($id, $perPage, $page)
    {
        return $this->MomentsRepository->all($id, $perPage, $page);
    }

    public function create($request)
    {
        if ($request->hasFile('img')) {
            $img = Common::upload('images', $request->file('img'));
        }
        $data = [
            'user_id' => $request->user_id,
            'description' => $request->user_id,
            'img' =>  $img ?? ''
        ];
        $this->MomentsRepository->create($data);
        return true;
    }

    public function update($id, $request)
    {

        $data = [
            'name' => $request->name,
        ];
        if ($request->hasFile('img')) {
            $data['img'] = Common::upload('images', $request->file('img'));
        }
        $this->MomentsRepository->update($data, $id);
        return true;
    }

    public function delete($id)
    {

        $data = $this->MomentsRepository->findOrFail($id);
        $data->delete();
        return true;
    }

    public function show($id)
    {
        return $this->MomentsRepository->find($id);
    }

    public function search($uuid)
    {

        return $this->MomentsRepository->search($uuid);
    }
    public function get_user_moments($user_id)
    {

        return $this->MomentsRepository->get_user_moments($user_id);
    }
}
