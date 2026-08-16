<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use App\Tik\Repositories\ReportUserRepository;

class ReportUserSerVice
{

    public function __construct(
        private readonly ReportUserRepository $reportUserRepository,
    ) {}


    public function reportUser($authId, $userReportId, $request)
    {
        $type_report = $request->type_report;
        $report_content = $request->report_content;

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $image = Common::upload('profile', $img);
            // $profile->avatar = $image;
        }
        $data = [
            'type' => $type_report,
            'report_details' => $report_content,
            'user_id' => $userReportId,
            'Reporter_id' => $authId,
            'image' => @$image,
        ];
        $this->reportUserRepository->create($data);
    }
}
