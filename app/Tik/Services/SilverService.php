<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\SilverRepository;


class SilverService
{
    public function __construct(private readonly SilverRepository $silverRepository) {}

    public function all($id, $perPage, $page)
    {
        return $this->silverRepository->all($id, $perPage, $page);
    }

    public function create($request)
    {

        $this->silverRepository->create($request->all());
        return true;
    }

    public function update($id, $request)
    {

        $this->silverRepository->update($request->all(), $id);
        return true;
    }

    public function delete($id)
    {

        $data = $this->silverRepository->findOrFail($id);
        $data->delete();
        return true;
    }

    public function show($id)
    {
        return $this->silverRepository->findOrFail($id);
    }
}
