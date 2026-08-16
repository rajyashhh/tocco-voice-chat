<?php

namespace App\Tik\Services;

use App\Tik\Repositories\AdminRepository;
use App\Tik\Repositories\AgencyHostInviteRepository;
use App\Tik\Repositories\UserRepository;
use Exception;

class AgencyHostInviteService
{
    public function __construct(
        private readonly AgencyHostInviteRepository $agencyHostInviteRepository,
        private readonly UserRepository $userRepository,
        private readonly AdminRepository $adminRepository,
    ) {}


    public function inviteAgency($request)
    {
        $user = $this->get_user($request);
        if (!$user) throw new Exception('لا يوجد مستخدم!');

        $newHost = $this->userRepository->findById($request->user_id2);


        if ($newHost->agency_id != 0) throw new Exception('المستخدم موجود في وكاله!');

        $check = $this->agencyHostInviteRepository->check($user->agency_id, $newHost->id);
        if ($check != null && $check->created_at->addDays(7) > now() && $check->status == 0)  throw new Exception('لم يمر علي اخر دعوه 7 ايام!');

        $data = [
            'user_invite_id' => $user->id,
            'agency_id' => $user->agency_id,
            'user_id'  => $newHost->id,
            'status' => 0,
        ];
        $this->agencyHostInviteRepository->create($data);
        return true;
    }

    public function hostInvitation()
    {
        $user = $this->get_user(request());
        if (!$user) throw new Exception('لا يوجد مستخدم!');
        return $this->agencyHostInviteRepository->getByAgencyId($user->agency_id);
    }

    public function inviteAction($request)
    {
        $user = $this->get_user(request());
        if (!$user)   throw new Exception('لا يوجد مستخدم!');

        $invitation = $this->agencyHostInviteRepository->findOrFail($request->invite_id);

        if ($invitation->created_at->addDays(7) < now()) {
            $this->agencyHostInviteRepository->updateStatus($invitation,3);
            throw new Exception('لقد مر اكثر من 7 ايام علي الدعوه');
        }
        $this->agencyHostInviteRepository->updateStatus($invitation,$request->status);
        if ($request->status == 1) {
            $this->userRepository->updateAgencyId($user, $invitation->agency_id);
        }

        return true;
    }

    public function get_user($request)
    {
        if ($request->user_id) {
            $admin = $this->adminRepository->findById($request->user()->id);
            if (isset($admin) && $admin->isRole("admin")) {
                $user = $this->userRepository->findById($request->user_id);
            } else {
                $user = null;
            }
        } else {
            $user = $this->userRepository->findById($request->user()->id);
        }
        return $user;
    }
}
