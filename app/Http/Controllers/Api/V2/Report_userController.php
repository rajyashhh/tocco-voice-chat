<?php

namespace App\Http\Controllers\Api\V2;

use Exception;
use App\Helpers\Common;
use App\Models\Report_user;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;

class Report_userController extends Controller
{
    public function ReportUser(Request $request)
    {
        $reporter_id = $request->user()->id;
        if (!$reporter_id) return Common::apiResponse(0, 'un_auth');

        $user_id = $request->id;
        $type_report = $request->type_report;
        $report_content = $request->report_content;

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $image = Common::upload('profile', $img);
            // $profile->avatar = $image;
        }

        $add = Report_user::create([

            'type' => $type_report,
            'report_details' => $report_content,
            'user_id' => $user_id,
            'Reporter_id' => $reporter_id,
            'image' => @$image,
        ]);

        if ($add) {
            return Common::apiResponse(true, 'has been sent', 200);
        }
        return Common::apiResponse(false, 'user not found', [], 404);
    }

    public function userReport($id)
    {
        try {
            $data = Report_user::where('user_id', $id)->with('Reporter')->get();
            return Common::apiResponse(true, 'done',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
