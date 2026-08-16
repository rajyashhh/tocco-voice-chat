<?php

namespace App\Admin\Controllers;

use App\Models\PaymentMethodHistory;
use App\Services\FawryPaymentService;
use App\Services\PaymobPaymentService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentMethodController extends AdminController
{
    protected function title()
    {
        return trans('Payment Methods');
    }

    protected function grid()
    {
        \Admin::js('js/admin/fawry.js');
        $grid = new Grid(new PaymentMethodHistory());
        $grid->model()->orderByDesc('id');

        if (!request()->has('status')) {
            $grid->model()->where('status', 'paid');
        }
        $grid->filter(function($filter) {
            $filter->expand();
            $filter->equal('status')->select(['pending' => 'pending', 'paid' => 'paid','error' => 'error'])->default("paid");
        });

        $grid->column('id', __('Id'));
        $grid->column('amount', __('amount'));
        $grid->column('payment_method', __('payment method'));
        $grid->column('status', __('status'));
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        });
        $grid->disableActions();

        return $grid;


    }


    protected function detail($id)
    {
        $show = new Show(PaymentMethodHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name_ar', __('Name ar'));
        $show->field('name_en', __('Name en'));
        $show->field('route', __('URL'));

        return $show;
    }

    public function create(Content $content)
    {

        \Admin::js('js/admin/fawry.js');
         $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->view("admin.views.payment");


        return $content;
    }

    public function customStore(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|string',
            'link_type' => 'required|in:fawry,paymob'
        ]);

        $trx = PaymentMethodHistory::create([
            "amount" => $request->amount,
            "type" => $request->type,
            "link_type" => $request->link_type,
            "payment_method" => $request->link_type,
        ]);

        $trxId = $trx->id;
        $trx->utd_code = $trxId;
        $trx->save();
        
        $exterData = ["type" => $request->type, 'paymentType' => "revenue"];

        try {
            if ($request->link_type === 'fawry') {
                $fawryService = new FawryPaymentService();
                $paymentUrl = $fawryService->makePaymentLink($trxId, $request->amount, $exterData);
                
                if (isset($paymentUrl['status']) && $paymentUrl['status'] == 0) {
                      return response()->json($paymentUrl, 200);

                }
                
                return $paymentUrl;

                
            } elseif ($request->link_type === 'paymob') {
                $paymobService = new PaymobPaymentService();
                $paymentUrl = $paymobService->makePayment($trxId, $request->amount, $exterData);
                
                if (isset($paymentUrl['status']) && $paymentUrl['status'] == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => $paymentUrl['message'] ?? __('payment.paymob_error'),
                        'data' => $paymentUrl
                    ], 400);
                }
                
                $finalPaymentUrl = null;
                
                if (isset($paymentUrl['payment_url'])) {
                    $finalPaymentUrl = $paymentUrl['payment_url'];
                } elseif (is_string($paymentUrl)) {
                    $finalPaymentUrl = $paymentUrl;
                } elseif (is_array($paymentUrl)) {
                    $finalPaymentUrl = $paymentUrl['url'] ?? $paymentUrl['payment_url'] ?? current($paymentUrl);
                }
                
                if (!is_string($finalPaymentUrl) || !filter_var($finalPaymentUrl, FILTER_VALIDATE_URL)) {
                
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid payment URL received',
                        'data' => $paymentUrl
                    ], 400);
                }
                
                
                return response()->json([
                    'success' => true,
                    'message' => __('payment.paymob_success'),
                    'payment_url' => $finalPaymentUrl,
                    'data' => $paymentUrl
                ], 200);
            }

        } catch (\Exception $e) {
            $trx->update(['status' => 'error']);
            
            return response()->json([
                'success' => false,
                'message' => __('payment.processing_error') . ': ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => __('payment.invalid_link_type'),
        ], 400);
    }

}
