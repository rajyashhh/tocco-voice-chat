<?php

namespace App\Http\Controllers;

use App\Http\Services\NowPaymentsService;
use App\Models\NowpaymentOrder;
use Database\Seeders\config;
use Illuminate\Http\Request;

use Log;

use Illuminate\Support\Facades\Auth;

class NowPaymentsController extends Controller
{
    protected $nowPayments;

    public function __construct(NowPaymentsService $nowPayments)
    {
        $this->nowPayments = $nowPayments;
    }

    public function rechargeForm()
    {
        $data = $this->nowPayments->getCurrencies();

        return view('payments.now_payments.index', ['currencies' => $data['currencies']]);
    }


    public function createPayment(Request $request)
    {

        try {
            $invoice = $this->nowPayments->createInvoice($request);

            // تحقق أن invoice_url موجود
            if (isset($invoice['invoice_url'])) {

                // حفظ الفاتورة في قاعدة البيانات
                NowpaymentOrder::create([
                    'payment_id' => $invoice['id'] ?? null,
                    'pay_currency' => $request->currency,
                    'pay_amount' => $invoice['pay_amount'] ?? $request->amount,
                    'price_amount' => $request->amount,
                    'price_currency' => 'usd',
                    'payment_status' => 'waiting',
                    'order_id' => $invoice['order_id'] ?? uniqid(),
                    'invoice_url' => $invoice['invoice_url'],
                    'user_id' => Auth::id() ?? 1,
                    'pay_address' => $invoice['pay_address'] ?? '',
                    'amount_received' => 0.0,
                ]);

                return redirect()->to($invoice['invoice_url']);

            } elseif (isset($invoice['pay_address'])) {
                // في حال لم يرجع invoice_url لكن رجع عنوان محفظة، اعرض بيانات الدفع اليدوي
                return view('manual_payment', [
                    'address' => $invoice['pay_address'],
                    'amount' => $invoice['pay_amount'],
                    'currency' => $invoice['pay_currency'],
                    'order_id' => $invoice['order_id'] ?? uniqid(),
                ]);
            }

            return back()->with('error', 'لم يتم إنشاء رابط الفاتورة. قد تكون العملة غير مدعومة حالياً.');

        } catch (\Exception $e) {
            return back()->with('error', 'خطأ أثناء إنشاء الدفع: ' . $e->getMessage());
        }
    }

    public function getCurrencies(){

        $response = $this->nowPayments->getCurrencies();

        $currencies = collect($response['currencies'] ?? [])->map(function ($currency) {
            return [
                'currency'    => strtoupper($currency['currency']),
                'min_amount'  => $currency['min_amount'],
                'max_amount'  => $currency['max_amount'],
            ];
        })->sortBy('currency')->values();
        return response()->json([
            'data' => $currencies
        ]);
    }

    public function paymentStatus($payment){
        $data = $this->nowPayments->getPaymentStatus($payment);

        return response()->json([
            'data' => $data
        ]);
    }

    // public function paymentCallback(Request $request)
    // {
    //     // Handle IPN callback from Now Payments
    //     $paymentId = $request->input('payment_id');
    //     $status = $this->nowPayments->getPaymentStatus($paymentId);

    //     // Update your database or trigger actions based on payment status
    //     if($status['payment_status'] == 'paid'){
    //         NowpaymentOrder::where('payment_id', $paymentId)->update([
    //             'payment_status' => 'paid'
    //         ]);
    //     }
    //     // Example: Mark order as paid

    //     return response()->json(['status' => 'success']);
    // }

    public function paymentCallback(Request $request)
    {


    $paymentId = $request->input('payment_id');
    $status = $request->input('payment_status');

    // تحقق  حالة الدفع
    if ($status === 'paid') {


        // تحديث حالة الدفع في قاعدة البيانات
        $order = NowpaymentOrder::where('payment_id', $paymentId)->update([
            'payment_status' => 'paid'
        ]);

        // التحقق من نجاح التحديث في قاعدة البيانات
        if ($order) {

            return redirect()->route('payment.success');
        } else {

            return redirect()->route('payment.cancel');
        }
    } else {

        return redirect()->route('payment.cancel');
    }


    return response()->json(['status' => 'received']);
    }
}
