<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Emoji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmojiController extends Controller
{
    public function index()
    {
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Emoji::when($search, function ($q) use ($search) {
            $q->where('id', $search);
        })
            ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id)
    {
        $result = Emoji::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id)
    {
        $result = Emoji::findOrFail($id);

        $result->delete();
        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request)
    {
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        // Eventless mass delete: bypasses the Emoji observer, so invalidate explicitly.
        Emoji::whereIn('id', $ids)->delete();

        Cache::tags(['emojis'])->flush();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request)
    {

        $validatedData  = $request->validate([
            'pid'      => 'nullable|integer|exists:emojis,id',
            'name'     => 'required|string|max:255',
            'name_en'  => 'nullable|string|max:255',
            'emoji'    => 'nullable',
            't_length' => 'nullable|integer',
            'enable'   => 'boolean',
            'sort'     => 'nullable|integer',
        ]);

        if ($request->hasFile('emoji')) {
            $validatedData['emoji'] = Common::upload('images', $request->file('emoji'));
        }

        $emoji = Emoji::create($validatedData);

        return Common::apiResponse(true, 'Success', $emoji);
    }

    public function update($id, Request $request)
    {
        $validatedData = $request->validate([
            'pid'      => 'nullable|integer|exists:emojis,id',
            'name'     => 'required|string|max:255',
            'name_en'  => 'nullable|string|max:255',
            'emoji'    => 'nullable',
            't_length' => 'nullable|integer',
            'enable'   => 'boolean',
            'sort'     => 'nullable|integer',
        ]);

        $emoji = Emoji::findOrFail($id);

        if ($request->hasFile('emoji')) {
            $validatedData['emoji'] =   Common::upload('images', $request->file('emoji'));
        }

        $emoji->update($validatedData);


        return Common::apiResponse(true, 'Success');
    }

    public function update_status($id, Request $request)
    {
        $request->validate([
            'enable' => 'required'
        ]);


        $emoji = Emoji::findOrFail($id);

        $emoji->enable = $request->enable;

        $emoji->save();

        return Common::apiResponse(true, 'Success');
    }

    public function emojiPid()
    {
        $data = Emoji::query()->select('id', 'name')->where('enable', 1)->where('pid', 0)->get();
        $data = collect([['id' => 0, 'name' => 'root']])->merge($data);
        return Common::apiResponse(true, 'Success', $data);
    }
}
