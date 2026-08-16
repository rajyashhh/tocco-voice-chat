<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Models\User;
use App\Helpers\Common;
use App\Models\UserSallary;
use Illuminate\Http\Request;
use App\Models\RequestTakeSalary;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AcceptRequestToGetSalary;
use App\Notifications\RefuseRequestToGetSalary;
use App\Http\Resources\RequestTakeSalaryResource;

class RequestTakeSalaryController extends Controller
{

    public function all(Request $request)
    {
        try {
            $id = $request->id;
            $page = $request->page;
            $perPage = $request->per_page;
            $data = RequestTakeSalary::where("status", 0)->with('user', 'paymentWithDraw')->orderByDesc('id')->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($perPage, ['*'], 'page', $page);
            return Common::apiResponse(true, 'success', RequestTakeSalaryResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        try {
            $data = RequestTakeSalary::findOrFail($id);
            return Common::apiResponse(true, 'success', new RequestTakeSalaryResource($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason_rejected' => 'nullable',
            'status' => 'required|integer',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {

            $requestTakenSalary =   RequestTakeSalary::findOrFail($id);
            $requestTakenSalary->status = $request->status;
            $requestTakenSalary->reason_rejected = $request->reason_rejected;
            $requestTakenSalary->save();

            $user = User::query()->where('id', $requestTakenSalary->user_id)->first();
            $amount = $requestTakenSalary->amount;

            if ($request->status == 2) {

                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount - $amount"),
                        'pending_dollar' => DB::raw("pending_dollar - $amount")
                    ]
                );
                if ($user->email) Notification::route('mail',  $user->email)->notify(new RefuseRequestToGetSalary($amount, $request->reason_rejected));
                $reason = $request->reason_rejected;

                CustomNotification::acceptRequestToGetMony($user, 2, $reason, $amount);
            } elseif ($request->status == 1) {

                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'pending_dollar' => DB::raw("pending_dollar - $amount")
                    ]
                );
                CustomNotification::acceptRequestToGetMony($user, 1, '', $amount);
                if ($user->email) Notification::route('mail',  $user->email)->notify(new AcceptRequestToGetSalary($amount));
            }
            return Common::apiResponse(true, 'update successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function history(Request $request)
    {
        try {
            $id = $request->id;
            $page = $request->page;
            $perPage = $request->per_page;
            $status = $request->status;
            $data = RequestTakeSalary::where("status", "!=", 0)->with('user', 'paymentWithDraw')->orderByDesc('id')->when($status == 1 ||  $status == 2, function ($query) use ($status) {
                $query->where('status', $status);
            })->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($perPage, ['*'], 'page', $page);
            return Common::apiResponse(true, 'success', RequestTakeSalaryResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
