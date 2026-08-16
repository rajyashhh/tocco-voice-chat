<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\PaymentWithdrawType;
use App\Http\Controllers\Controller;
use App\Models\PaymentWithdrawField;

class WithdrawController extends Controller
{
    public function index()
    {
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = PaymentWithdrawType::when($search, function ($q) use ($search) {
            $q->where('id', $search);
        })
            ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id)
    {
        $result = PaymentWithdrawType::with('withdrawFields')->findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id)
    {
        $result = PaymentWithdrawType::findOrFail($id);
        $result->withdrawFields()->delete();
        $result->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request)
    {

        $request->validate([
            'ids' => 'required'
        ]);

        $ids  = explode(',', $request->ids);

        $paymentWithdrawTypes = PaymentWithdrawType::whereIn('id', $ids)->get();

        foreach ($paymentWithdrawTypes as $paymentWithdrawType) {
            $paymentWithdrawType->withdrawFields()->delete();
        }

        PaymentWithdrawType::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'name_en' => 'required|string',
            'image' => 'nullable|image',
            'min_value' => 'required|numeric',
            'exchange_rate' => 'required|numeric',
            'withdrawFields' => 'nullable',
        ]);


        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = Common::upload('images', $request->file('image'));
        }

        $paymentWithdrawType = PaymentWithdrawType::create($data);

        if ($request->has('withdrawFields')) {
            $paymentWithdrawTypes  = json_decode($request->withdrawFields, true);

            foreach ($paymentWithdrawTypes as &$withdrawField) { // Use &$withdrawField to modify the array directly
                $withdrawField['payment_withdraw_type_id'] = $paymentWithdrawType->id;
                PaymentWithdrawField::create($withdrawField);
            }
            // $paymentWithdrawType->withdrawFields()->attach($json_decoded);
        }

        return Common::apiResponse(true, 'Success', $paymentWithdrawType);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'name_en' => 'required|string',
            'image' => 'nullable',
            'min_value' => 'required|numeric',
            'exchange_rate' => 'required|numeric',
            'withdrawFields' => 'nullable',
        ]);

        $paymentWithdrawType = PaymentWithdrawType::findOrFail($id);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = Common::upload('images', $request->file('image'));
        }

        $paymentWithdrawType->update($data);

        if ($request->has('withdrawFields')) {
            $paymentWithdrawType->withdrawFields()->delete();
            $paymentWithdrawTypes  = json_decode($request->withdrawFields, true);
            foreach ($paymentWithdrawTypes as &$withdrawField) { // Use &$withdrawField to modify the array directly
                $withdrawField['payment_withdraw_type_id'] = $paymentWithdrawType->id;
                PaymentWithdrawField::create($withdrawField);
            }
        }

        return Common::apiResponse(true, 'Success', $paymentWithdrawType);
    }
}
