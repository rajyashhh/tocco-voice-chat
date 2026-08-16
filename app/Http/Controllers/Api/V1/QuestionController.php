<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Mail\SendCustomerService;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuestionController extends Controller
{
    public function questions()
    {
        $questions = Question::where("status",1)->get();
        return Common::apiResponse(1, '', $questions);
    }

    public function send_mail_to_customer_service(Request $request)
    {
        $message = $request->message;
        $email = env("CUSTOMER_SERVICE_EMAIL");

        Mail::to($email)->send(new SendCustomerService($message));
        return Common::apiResponse(1, 'تم الارسال بنجاح');
    }
}
