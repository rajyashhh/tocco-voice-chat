<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use  Modules\Vip\Repositories\OvipRepository;
use  Modules\Vip\Repositories\VipPrivilegeRepository;
use App\Tik\Repositoris\WareRepository;


class OvipService
{
    public function __construct(
        private readonly OvipRepository $ovipRepository,
        private readonly WareRepository $wareRepository,
        private readonly VipPrivilegeRepository $vipPrivilegeRepository,
    ) {}

    public function index()
    {
        return $this->ovipRepository->getBySortLevel();
    }

    public function create($request)
    {
        $image = null;
        if ($request->hasFile('image')) {
            $image = Common::upload('images', $request->file('image'));
        }
        $privileges = json_decode($request->privileges);
        $dataOvip = [
            'name' => $request->name,
            'level' => $request->level,
            'img' => $image ?? '',
            'price' => $request->price,
            'expire' => $request->expire,
            'exp' => $request->exp
        ];
        $ovip =  $this->ovipRepository->create($dataOvip);

        $ovip->privilegs()->sync($privileges);

        $notActive = $this->wareRepository->notActive($request->level);
        if ($notActive) {
            foreach ($privileges as $privilege) {
                $vip = $this->vipPrivilegeRepository->findById($privilege);
                if (isset($vip->type)) {
                    $this->wareRepository->updateActiveWithType($vip->type, $request->level);
                }
            }
        }
        return true;
    }

    public function show($id)
    {
        return $this->ovipRepository->findById($id);
    }
    public function showWithAllPrivileges($id)
    {
        $vipPrivileges = $this->vipPrivilegeRepository->all();
        $ovip = $this->ovipRepository->findById($id);
        $data = [
            'all_privileges' => $vipPrivileges,
            'o_vips' => $ovip,
        ];
        return $data;
    }

    public function update($request)
    {
        $dataOvip = [
            'name' => $request->name,
            'level' => $request->level,
            'price' => $request->price,
            'expire' => $request->expire,
            'exp' => $request->exp
        ];
        if ($request->hasFile('image')) {
            $dataOvip['img']= Common::upload('images', $request->file('image')); 
        } 
        $privileges = json_decode($request->privileges);
        $this->ovipRepository->update($dataOvip, $request->o_vip_id);
        $ovip = $this->ovipRepository->findById($request->o_vip_id);
        $ovip->privilegs()->sync($privileges);

        $notActive = $this->wareRepository->notActive($request->level);
        if ($notActive) {
            foreach ($privileges as $privilege) {
                $vip = $this->vipPrivilegeRepository->findById($privilege);
                if (isset($vip->type)) {
                    $this->wareRepository->updateActiveWithType($vip->type, $request->level);
                }
            }
        }
        return true;
    }

    public function allVIP($search)
    {
        return $this->vipPrivilegeRepository->listVip($search);
    }
}
