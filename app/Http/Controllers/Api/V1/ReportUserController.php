<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;


use App\Http\Controllers\Controller;
use App\Tik\Services\ReportUserSerVice;

class ReportUserController extends Controller
{
    public function __construct(private ReportUserSerVice $reportUserSerVice) {}

    public function ReportUser(Request $request)
    {
        $reporter_id = $request->user()->id;
        if (!$reporter_id) return Common::apiResponse(0, 'un_auth', 400);
        $user_id = $request->id;
        $this->reportUserSerVice->reportUser($reporter_id, $user_id, $request);
        return Common::apiResponse(true, 'has been sent', 200);
    }
}
