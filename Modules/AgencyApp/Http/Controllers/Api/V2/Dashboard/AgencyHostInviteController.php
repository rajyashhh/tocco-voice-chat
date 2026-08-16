<?php

namespace Modules\AgencyApp\Http\Controllers\Api\V2\Dashboard;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Tik\Services\AgencyHostInviteService;
use Modules\AgencyApp\Transformers\AgencyInvitationResource;


class AgencyHostInviteController extends Controller
{
    protected $agencyHostInviteService;

    public function __construct(AgencyHostInviteService $agencyHostInviteService)
    {
        $this->agencyHostInviteService = $agencyHostInviteService;
    }

    public function invite_user_to_hostAgency(Request $request)
    {
        if (!$request->user_id2) {
            return Common::apiResponse(0, 'المستخدم مطلوب!',  423);
        }

        try {
            $this->agencyHostInviteService->inviteAgency($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'تم ارسال الدعوه بنجاح', [],  200);
    }

    public function agencyHostInvitation(Request $request)
    {
        try {
            $invitations =   $this->agencyHostInviteService->hostInvitation($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, '', AgencyInvitationResource::collection($invitations),  200);
    }

    public function actionInvitation(Request $request)
    {
        if (!$request->invite_id || !$request->status) {
            return Common::apiResponse(0, 'البيانات غير مكتمله',  423);
        }
        try {
            $this->agencyHostInviteService->inviteAction($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'تم التعديل بنجاح', [],  200);
    }
}
