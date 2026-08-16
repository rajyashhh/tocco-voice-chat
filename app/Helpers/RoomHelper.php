<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\RoomAdministrator;

class RoomHelper
{
    public static function checkUserIsAdminOrOwner(string $admins, $ownerId): bool
    {
        $userId = Auth::id();
        $admins = explode (',',$admins);
        return ($userId == $ownerId || in_array ($userId, $admins) );
    }

    /**
     * Per-power authorization for a room moderation action.
     *
     * The room OWNER can do anything. A room admin can do a given $power only
     * when their granted permission set contains it; a NULL permission set means
     * "all powers" (legacy admins, and admins promoted with the "all" toggle).
     * As a back-compat fallback, an admin listed only in the legacy CSV
     * (rooms.room_admin) — with no row in room_administrators yet — keeps full
     * powers, so this can be enforced additively without breaking older rooms.
     *
     * @param int|null $roomId
     * @param string   $power  one of the 9 granular powers
     *                          (kick_from_room, ban_users, mute_users,
     *                          remove_from_mic, lock_seats, music,
     *                          manage_requests, manage_chat, invite_to_mic)
     */
    public static function can(?int $roomId, string $power): bool
    {
        $userId = Auth::id();
        if (!$roomId || !$userId) return false;

        $room = Room::find($roomId);
        if (!$room) return false;

        // Owner is omnipotent.
        if ($userId == $room->uid) return true;

        $admin = RoomAdministrator::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        if ($admin) {
            $perms = $admin->permissions; // array | null (null = all powers)
            if ($perms === null) return true;
            return in_array($power, $perms, true);
        }

        // Legacy CSV admin (not yet migrated to room_administrators) = full powers.
        $legacy = explode(',', $room->room_admin ?? '');
        return in_array((string) $userId, $legacy, true);
    }

    public function gameWhoWin($answer_player_one,$answer_player_two,$record_game){
        if ($answer_player_one == 0 && $answer_player_two == 0) {
            $record_game->type = "equal";
            $record_game->save();
        }elseif ($answer_player_one == 0 && $answer_player_two == 1) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_two_id;
            $record_game->save();
        }elseif ($answer_player_one == 0 && $answer_player_two == 2) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_one_id;
            $record_game->save();
        }elseif ($answer_player_one == 1 && $answer_player_two == 0) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_one_id;
            $record_game->save();
        }elseif ($answer_player_one == 1 && $answer_player_two == 1) {
            $record_game->type = "equal";
            $record_game->save();
        }elseif ($answer_player_one == 1 && $answer_player_two == 2) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_two_id;
            $record_game->save();
        }elseif ($answer_player_one == 2 && $answer_player_two == 0) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_two_id;
            $record_game->save();
        }elseif ($answer_player_one == 2 && $answer_player_two == 1) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_one_id;
            $record_game->save();
        }elseif ($answer_player_one == 2 && $answer_player_two == 2) {
            $record_game->type = "equal";
            $record_game->save();
        }
    }

    public function gameWhoWinInZahr($answer_player_one,$answer_player_two,$record_game){
        if ($answer_player_one == $answer_player_two) {
            $record_game->type = "equal";
            $record_game->save();
        }elseif ($answer_player_one > $answer_player_two) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_one_id;
            $record_game->save();
        }elseif ($answer_player_two > $answer_player_one) {
            $record_game->type = "win";
            $record_game->player_win_id = $record_game->player_two_id;
            $record_game->save();
        }
    }

}
