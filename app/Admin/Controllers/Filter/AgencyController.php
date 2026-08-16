<?php

namespace App\Admin\Controllers\Filter;

use App\Models\Agency;
use Illuminate\Http\Request;

class AgencyController
{
    public function __invoke(Request $request)
    {
        $q = $request->get('q');

        return Agency::query()
            ->where(function ($query) use ($q) {
                $query
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%");
            })
            ->paginate(null, ['id', 'name as text']);
    }
}
