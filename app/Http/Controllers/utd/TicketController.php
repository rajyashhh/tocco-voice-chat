<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(){

        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = Ticket::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }


    public function show($id){
        $result = Ticket::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'contact_num' => 'required',
            'problem' => 'required',
            'description' => 'required',
            'img' => 'nullable',
            'status' => 'required'
        ]);


        if($request->hasFile('img')){
            $img = Common::upload('images', $request->file('img'));
            $validated['img'] = $img;
        }

        $ticket = Ticket::create($validated);

        return Common::apiResponse(true, 'Success', $ticket);
    }

    public function delete($id){
        Ticket::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',',$request->ids);

        Ticket::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function update($id, Request $request){
        $validated = $request->validate([
            'contact_num' => 'required',
            'problem' => 'required',
            'description' => 'required',
            'img' => 'nullable',
            'status' => 'required'
        ]);

        $ticket = Ticket::findOrFail($id);
        if($request->hasFile('img')){
            $img = Common::upload('images', $request->file('img'));
            $validated['img'] = $img;
        }

        $ticket->update($validated);

        return Common::apiResponse(true, 'Success');
    }

    public function status($id, Request $request){
        $request->validate([
            'status' => 'required'
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => $request->status
        ]);

        return Common::apiResponse(true, 'Success');
    }
}
