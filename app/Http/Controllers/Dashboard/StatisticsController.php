<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Room;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
public function summary()
    {
        return response()->json([
            "users" => User::count(),
            "usersChange" => rand(1, 20),

            "rooms" => Room::count(),
            "roomsChange" => rand(1, 15),

            "agencies" => DB::table("agencies")->count(),

            "giftsMonth" => rand(50000, 150000),
            "giftsChange" => rand(2, 15),

            "sparkUsers" => $this->random(20, 100, 300),
            "sparkRooms" => $this->random(20, 40, 120),
            "sparkAgencies" => $this->random(20, 10, 80),
        ]);
    }

    public function charts()
    {
        return response()->json([
            "liveSeries" => $this->random(30, 100, 900),
            "usersDonut" => [
                User::where('gender', 'male')->count(),
                User::where('gender', 'female')->count(),
                User::whereNull('gender')->count(),
            ],

            "heatmap" => $this->random(12, 50, 300),
        ]);
    }

    public function topRooms()
    {
        $rooms = Room::limit(10)->get()->map(function ($r) {
            return [
                "id" => $r->id,
                "name" => $r->name,
                "avatar" => $r->image,
                "viewers" => rand(50, 3000),
                "gifts" => rand(20000, 90000),
                "agency" => "Agency " . rand(1, 5),
            ];
        });

        return response()->json([
            "topRooms" => $rooms
        ]);
    }

    private function random($count, $min, $max)
    {
        return collect(range(1, $count))->map(fn() => rand($min, $max))->toArray();
    }
}
