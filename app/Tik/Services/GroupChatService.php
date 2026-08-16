<?php

namespace App\Tik\Services;


use App\Helpers\Common;
use Modules\Reals\Entities\Real;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\GroupChatRepository;


class GroupChatService
{
    public function __construct(
        private readonly GroupChatRepository $groupChatRepository,
        private readonly UserRepository $userRepository

    ) {}

    public function index($user)
    {
        $data = $this->groupChatRepository->getWithPaginate();
        $this->userRepository->updateUnreadCountMessage($user);
        return $data;
    }

    public function create($user, $request, $costGroupChat)
    {
        $this->userRepository->decrementUserCoins($user, $costGroupChat);
        //add image to group chat
        $image = null;
        if ($request->hasFile('image')) {
            $image = Common::upload('group-chat', $request->file('image'));
        }
        if ($request->image_url) {
            $image = $request->image_url;
        }

        $data = [
            'text' => $request->text,
            'user_id' => $user->id,
            'image' => $image ?? '',
            'parent_id' => $request->message_id,
        ];
        $groupChatMessage = $this->groupChatRepository->create($data);
        if ($request->message_type != null && $request->message_type == 'reel')  $this->countReel($request->text);
        $this->userRepository->incrementUnreadMessage($user);
        return $groupChatMessage;
    }

    public function countReel($data)
    {
        $parts = explode(':', str_replace("\n", ':', $data));
        $reelId = $parts[4] ?? null;
        $reel = Real::find($reelId);
        if (!$reel) return true;
        $reel->share_num += 1;
        $reel->save();
    }
}
