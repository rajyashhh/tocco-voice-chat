<?php

namespace App\Http\Controllers;

use App\Services\GiftLoadTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class TestsController extends Controller
{
public function discrepancyView(Request $request)
{
    // الشهر والسنة المطلوبين أو الحالي
    $month = $request->query('month', Carbon::now()->month);
    $year = $request->query('year', Carbon::now()->year);

    // جلب كل المستخدمين الذين لديهم أي سجل في الشهر المحدد
    $usersMonthly = DB::table('monthly_diamond_receives')
        ->where('month', $month)
        ->where('year', $year)
        ->get();

    $results = [];

    foreach ($usersMonthly as $monthly) {
        $userId = $monthly->user_id;

        // جمع عدد الماسات فعلياً من gift_logs: عدد الهدايا * السعر
        $totalGifts = DB::table('gift_logs')
            ->where('receiver_id', $userId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('SUM(giftPrice) as total')
            ->value('total') ?? 0; // fallback للصفر إذا لا يوجد هدايا

        // المقارنة بعد تحويل القيم إلى float
        $isDifferent = floatval($totalGifts) != floatval($monthly->monthly_diamond_received);
        if ($isDifferent) {
            $results[] = [
                'user_id' => $userId,
                'registered' => floatval($monthly->monthly_diamond_received),
                'actual' => floatval($totalGifts),
            ];
        }
    }

    return view('tests.discrepancy', [
        'results' => $results,
        'month' => $month,
        'year' => $year,
    ]);
}


 public function form()
    {
        return view('tests.load-test');
    }

    
    public function run(Request $request, GiftLoadTestService $service)
{
    $request->validate([
        'count' => 'required|integer|min:1',
        'concurrency' => 'required|integer|min:1',
        'token' => 'required|string',
        'id' => 'required|integer',
        'owner_id' => 'required|integer',
        'toUid' => 'required|integer',
        'num' => 'required|integer|min:1',
        'url'=> 'required|string',
    ]);

    $data = $service->run($request->all());

    return view('tests.load-test', $data);
}




 public function lucky_form()
    {
        return view('tests.lucky-load-test');
    }

public function lucky_run(Request $request, GiftLoadTestService $service)
    {
        $request->validate([
            'count' => 'required|integer|min:1',
            'concurrency' => 'required|integer|min:1',
            'token' => 'required|string',
            'id' => 'required|integer',
            'owner_id' => 'required|integer',
            'toUid' => 'required|integer',
            'num' => 'required|integer|min:1',
            'url'=> 'required|string',
        ]);

        $data = $service->luckyRun($request->all());

        return view('tests.lucky-load-test', $data);
    }
}
