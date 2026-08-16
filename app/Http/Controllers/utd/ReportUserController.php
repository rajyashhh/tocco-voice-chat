<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReportUserController extends Controller
{
    public function index(){

        $search = request('search');
        $year = request('year');
        $month= request('month');
        $agency = request('agency');

        $perPage = request('per_page') ?? 10;

        $result = User::with('liveTime', 'reals', 'moments')->when($search,function($q)use($search){
            $q->where('uuid', 'like', "%{$search}%");
        })
        ->when($year, function($q) use($year){
            $q->whereYear('created_at', $year);
        })
        ->when($month, function($q) use($month){
            $q->whereMonth('created_at', $month);
        })
        ->when($agency,function($q) use($agency){
            $q->where('agency_id', $agency);
        })
        ->paginate($perPage)
        ->through(function($user){
            return [
                'id' => $user->id,
                'name' => $user->name,
                'uuid' => $user->uuid,
                'total_days' => $this->calculateTotalDays($user),
                'reals_count' => $this->calculateRealsCount($user),
                'moment_count' => $this->calculateMomentCount($user),
                'total_hours' => $this->calculateTotalHours($user),
                'filtered_salary' => $this->calculateFilteredSalary($user),
                'current_salary' => $user->salary,
            ];
        });

        return Common::apiResponse(true,'Success', $result);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'google_id' => 'nullable|string|max:255',
            'huawei_id' => 'nullable|string|max:255',
            'facebook_id' => 'nullable|string|max:255',
            'di' => 'nullable|numeric',
            'coins' => 'nullable|numeric',
            'room_coins' => 'nullable|numeric',
            'flowers' => 'nullable|numeric',
            'flowers_value' => 'nullable|numeric',
            'gold' => 'nullable|numeric',
            'is_leader' => 'nullable|boolean',
            'is_sign' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'is_points_first' => 'nullable|boolean',
            'online_time' => 'nullable|integer',
            'dress_1' => 'nullable|integer',
            'dress_2' => 'nullable|integer',
            'dress_3' => 'nullable|integer',
            'dress_4' => 'nullable|integer',
            'nickname' => 'nullable|string|max:255',
            'mykeep' => 'nullable|string|max:255',
            'system' => 'nullable|string|max:255',
            'channel' => 'nullable|string|max:255',
            'img_1' => 'nullable|string|max:255',
            'points' => 'nullable|integer',
            'device_token' => 'nullable|string|max:255',
            'scale' => 'nullable|integer',
            'now_room_uid' => 'nullable|integer',
            'bio' => 'nullable|string',
            'agency_id' => 'nullable|integer',
            'family_id' => 'nullable|integer',
            'is_host' => 'nullable|boolean',
            'whatsapp' => 'nullable|string|max:255',
            'old_usd' => 'nullable|numeric',
            'target_usd' => 'nullable|numeric',
            'target_token_usd' => 'nullable|numeric',
            'uuid' => 'nullable|string|max:255',
            'is_gold_id' => 'nullable|boolean',
            'chat_id' => 'nullable|string|max:255',
            'notification_id' => 'nullable|string|max:255',
            'vip' => 'nullable|integer',
            'sub_sender_level' => 'nullable|integer',
            'sub_receiver_level' => 'nullable|integer',
            'sub_sender_num' => 'nullable|integer',
            'sub_receiver_num' => 'nullable|integer',
            'salary' => 'nullable|numeric',
            'monthly_diamond_send' => 'nullable|integer',
            'total_diamond_send' => 'nullable|integer',
            'monthly_diamond_received' => 'nullable|integer',
            'total_diamond_received' => 'nullable|integer',
            'sender_level' => 'nullable|integer',
            'received_level' => 'nullable|integer',
            'type_user' => 'nullable|integer',
            'is_manger' => 'nullable|boolean',
            'apple_id' => 'nullable|string|max:255',
            'today_days' => 'nullable|integer',
            'monthly_days' => 'nullable|integer',
            'total_days' => 'nullable|integer',
            'lang' => 'nullable|string|max:2',
            'lan' => 'nullable|string|max:2',
            'unread_count_message' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'current_app_version' => 'nullable|integer',
        ]);

        // Hash the password before saving
        $validatedData['password'] = Hash::make($validatedData['password']);

        // Create the user
        $user = User::create($validatedData);

        // Return the created user as a JSON response
        return Common::apiResponse(true, 'Success', $user);
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);
        User::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }
    protected function calculateTotalDays($user)
    {
        $month = request('month');
        $year = request('year');
        if ($month && $year) {
            return DB::table('live_times')
                ->select('uid', DB::raw('COUNT(*) AS entry_count'))
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->where('uid', $user->id)
                ->groupBy('uid', DB::raw('DATE(created_at)'))
                ->havingRaw('SUM(hours) > 1')
                ->count();
        }

        return $user->total_days;
    }

    protected function calculateRealsCount($user)
    {
        $month = request('month');
        $year = request('year');
        if ($year && $month) {
            return $user->reals()
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count();
        }

        return $user->reals()->count();
    }

    protected function calculateMomentCount($user)
    {
        $month = request('month');
        $year = request('year');
        if ($year && $month) {
            return $user->moments()
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count();
        }

        return $user->moments()->count();
    }

    protected function calculateTotalHours($user)
    {
        $month = request('month');
        $year = request('year');
        if ($year && $month) {
            return $user->liveTime()
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('hours');
        }

        return $user->liveTime()->sum('hours');
    }

    protected function calculateFilteredSalary($user)
    {
        $month = request('month');
        $year = request('year');
        if ($year && $month) {
            return $user->getSalary($month, $year);
        }

        return $user->salary;
    }
}
