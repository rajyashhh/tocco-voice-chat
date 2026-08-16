<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $pages = Page::when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $pages);
    }

    public function store(Request $request){

        $page = Page::create([
            'name' => $request->name,
            'content' => $request->content,
            'content_en' => $request->content_en
        ]);

        return Common::apiResponse(true, 'Success', $page);
    }
    public function update($id, Request $request){
        $page = Page::findOrFail($id);

        $page->update([
            'name' => $request->name,
            'content' => $request->content,
            'content_en' => $request->content_en
        ]);

        return Common::apiResponse(true, 'Success');
    }

    public function show($id){

        $page = Page::findOrFail($id);

        return Common::apiResponse(true, 'Success', $page);
    }

    public function delete($id){
        $page = Page::findOrFail($id);

        $page->delete();

        return Common::apiResponse(true, 'Success');
    }
}
