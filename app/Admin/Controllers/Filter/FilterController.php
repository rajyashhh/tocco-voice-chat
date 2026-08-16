<?php

namespace App\Admin\Controllers\Filter;

use App\Models\Room;

use Illuminate\Http\Request;

class FilterController
{
    public function rooms(Request $request)
    {
        $q = $request->get('q');

        return Room::query()
            ->where(function ($query) use ($q) {
                $query
                    ->orWhere('room_name', 'like', "%{$q}%")
                    ->orWhere('id', $q)->orWhereHas('owner', function ($query) use ($q) {
                        $query->where('uuid', $q);
                    });
            })
            ->paginate(null, ['id', 'room_name as text']);
    }
}
