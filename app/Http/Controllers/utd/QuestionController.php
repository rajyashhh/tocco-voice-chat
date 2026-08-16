<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Question::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }

    public function show($id){
        $result = Question::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function delete($id){
        Question::findOrFail($id)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        Question::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }

    public function store(Request $request){
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'status' => 'required'
        ]);


        $result = Question::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'status' => $request->status
        ]);

        return Common::apiResponse(true,'Success', $result);
    }

    public function update($id, Request $request){
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'status' => 'required'
        ]);

        $result = Question::findOrFail($id);

        $result->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'status' => $request->status
        ]);

        return Common::apiResponse(true,'Success');
    }

    public function update_status($id, Request $request){
        $request->validate([
            'status' => 'required'
        ]);

        $result = Question::findOrFail($id);

        $result->update([
            'status' => $request->status
        ]);

        return Common::apiResponse(true,'Success');
    }
}
