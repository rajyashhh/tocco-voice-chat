<?php

namespace App\Tik\Services;

use App\Tik\Repositories\TargetRepository;


class TargetService
{
    public function __construct(
        private readonly TargetRepository $targetRepository,
    ) {}

    public function index($request)
    {
        return $this->targetRepository->all($request);
    }

    public function create($request)
    {
        if (isset($request->moment)) {
            $arrayMoment = array_values(json_decode($request->moment));
            // Convert the values to a comma-separated string
            $moment = implode(',', $arrayMoment);
        }
        if (isset($request->reel)) {
            $arrayReel = array_values(json_decode($request->reel));
            // Convert the values to a comma-separated string
            $reel = implode(', ', $arrayReel);
        }

        $data = [
            'level'       => $request->level,
            'diamonds'        => $request->diamonds,
            'usd' => $request->usd,
            'hours'        => $request->hours,
            'days'        => $request->days,
            'agency_share'        => $request->agency_share,
            'moment'        => $moment ?? null,
            'reel'        => $reel ?? null,
        ];
        $this->targetRepository->create($data);
        return true;
    }

    public function show($id)
    {
        return $this->targetRepository->findById($id);
    }

    public function update($id, $request)
    {
        if (isset($request->moment)) {
            $arrayMoment = array_values(json_decode($request->moment));
            // Convert the values to a comma-separated string
            $moment = implode(',', $arrayMoment);
        }
        if (isset($request->reel)) {
            $arrayReel = array_values(json_decode($request->reel));
            // Convert the values to a comma-separated string
            $reel = implode(', ', $arrayReel);
        }
        $data = [
            'level'       => $request->level,
            'diamonds'        => $request->diamonds,
            'usd' => $request->usd,
            'hours'        => $request->hours,
            'days'        => $request->days,
            'agency_share'        => $request->agency_share,
            'moment'        => $moment ?? null,
            'reel'        => $reel ?? null,
        ];
        $this->targetRepository->update($data, $id);
        return true;
    }
}
