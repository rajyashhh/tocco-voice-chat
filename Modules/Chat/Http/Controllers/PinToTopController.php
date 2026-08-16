<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\PinToTop;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Chat\Http\Services\PinToTopService;

class PinToTopController extends Controller
{

    public function __construct(public PinToTopService $pinToTopService)
    {

    }

    public function index(Request $request)
    {
        $user = User::with('chats')->find($request->user()->id);
        return $user->chats;
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'chat_id' => 'required|exists:chat_rooms,id',
        ]);
        $user = $request->user();


        $response = $this->pinToTopService->store($user->id, $request->user_id);

        // Clear cached chat rooms so pinned order refreshes
        \Cache::forget("chat_rooms_{$user->id}");

        return response()->json($response, $response['status']);
    }


    public function destroy(Request $request ,string $id)
    {

        $user = $request->user();
        $response = $this->pinToTopService->removePinFromTop($user->id, $id);

        // Clear cached chat rooms so pinned order refreshes
        \Cache::forget("chat_rooms_{$user->id}");

        return response()->json($response, $response['status']);
    }
}
