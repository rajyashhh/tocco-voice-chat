<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\GroupChat;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
    public function index()
    {
        $per_page = request('per_page') ?? 10;
        $search = request('search');
        $groups = GroupChat::where(function ($q) use ($search) {
            $q->whereHas('user', function ($q2) use ($search) {
                $q2->where('name', 'LIKE', "%$search%")
                    ->orWhere('uuid', $search);
            });
        })->with('user')->paginate($per_page);

        return Common::apiResponse(1, 'success', $groups, 200);
    }

    public function store(Request $request)
    {

        $chat = GroupChat::create([
            'text' => $request->text,
            'user_id' => $request->user_id
        ]);

        return Common::apiResponse(1, 'success', $chat, 200);
    }

    public function show($id)
    {
        $group = GroupChat::findOrFail($id);

        return Common::apiResponse(1, 'success', $group, 200);
    }

    public function update(Request $request, $id)
    {
        GroupChat::findOrFail($id)->update([
            'text' => $request->text,
            'user_id' => $request->user_id
        ]);

        return Common::apiResponse(1, 'success', [], 200);
    }

    public function add_experience_points(Request $request)
    {

        $conf = Config::where('name', 'send_world_chat')->first();
        if (!$conf) {
            config::create([
                'name'  => 'send_world_chat',
                'value' => $request->number,
            ]);
        } else {
            $conf->value = $request->number;
            $conf->save();
        }

        return Common::apiResponse(1, 'success', [], 200);
    }

    public function destroy($id)
    {
        GroupChat::findOrFail($id)->delete();

        return Common::apiResponse(1, 'success', [], 200);
    }
}
