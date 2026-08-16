<?php

namespace Modules\Chat\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contact discovery (chat rebuild §3) — WhatsApp-style "who from my phone book is
 * on the app". The client uploads the device's phone numbers (over the existing
 * authenticated TLS channel); the server matches them against registered users by
 * the LAST 10 digits (country-code/leading-zero agnostic — see the functional
 * index migration) and returns the matched users. The client keeps the local
 * saved name and shows it next to each match (the server never sees contact
 * names — only numbers it already holds for its own users).
 */
class ContactsController extends Controller
{
    /** Hard ceiling on phones per request to bound work on the users table. */
    private const MAX_PHONES = 2000;

    /**
     * POST /api/contacts/match
     * Body: { "phones": ["+201012345678", "01012345678", ...] }
     * Returns the registered users whose phone matches (by last 10 digits), each
     * with `phone_key` (the last 10 digits) so the client can map it back to the
     * locally saved contact name.
     */
    public function match(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phones'   => ['required', 'array', 'max:' . self::MAX_PHONES],
            'phones.*' => ['nullable', 'string', 'max:32'],
        ]);

        // Normalize each number to its last 10 significant digits.
        $last10 = collect($data['phones'])
            ->map(fn ($p) => preg_replace('/\D+/', '', (string) $p))
            ->filter(fn ($d) => strlen($d) >= 7)
            ->map(fn ($d) => substr($d, -10))
            ->unique()
            ->values();

        if ($last10->isEmpty()) {
            return Common::apiResponse(true, __('success'), [], 200);
        }

        $callerId = (int) $request->user()->id;

        $users = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->whereNotNull('users.phone')
            ->whereIn(DB::raw('RIGHT(users.phone, 10)'), $last10->all())
            ->where('users.id', '!=', $callerId)
            ->limit(1000)
            ->get([
                'users.id',
                'users.uuid',
                'users.name',
                DB::raw('RIGHT(users.phone, 10) as phone_key'),
                'profiles.avatar',
            ]);

        $result = $users->map(fn ($u) => [
            'id'        => (int) $u->id,
            'uuid'      => $u->uuid,
            'name'      => $u->name,
            'avatar'    => $u->avatar,
            'phone_key' => $u->phone_key,
        ])->values();

        return Common::apiResponse(true, __('success'), $result, 200);
    }
}
