<?php

namespace App\Http\Controllers\Dashboard\Reports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Reports\AdminMomentReports;
use App\Http\Resources\Dashboard\Reports\AdminReealsReports;
use App\Http\Resources\Dashboard\Reports\AdminTicketsResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Modules\Moment\Entities\ReportMoment;
use Modules\Reals\Entities\ReportReals;

class AdminReportsController extends Controller
{

    public function moment(Request $request)
    {
        $data = ReportMoment::whereHas('moment')->with('moment')->orderBy('id','desc')->paginate(10);
        return AdminMomentReports::collection($data);
    }

    public function reels(Request $request)
    {
        $data = ReportReals::whereHas('reel')->with('reel')->orderBy('id','desc')->paginate(10);
        return AdminReealsReports::collection($data);
    }
    public function tickets(Request $request)
    {
        $data = Ticket::orderBy('id','desc')->paginate(10);
        return AdminTicketsResource::collection($data);
    }

    public function delete_tickets( $id)
    {
        $data = Ticket::find($id);
        $data->delete();
        return 200;
    }



}
