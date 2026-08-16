<?php

namespace Modules\Region\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Region\Entities\AreaManager;

class AreaManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function areaManger(Request $request)
    {

        $key = $request->q;
        $page = $request->get('page', 1);
        $perPage = 10;
        $admin = AreaManager::selectRaw('concat(COALESCE(username, ""), " - ", id) as name, id')
            ->where(function ($query) use ($key) {
                $query->where('username', 'like', '%' . $key . '%')
                    ->orWhere('id', 'like', '%' . $key . '%');
            })
            ->paginate($perPage, ['*'], 'page', $page);
        return response()->json($admin);
    }
}
