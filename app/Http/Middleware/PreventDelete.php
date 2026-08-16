<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\MessageBag;
class PreventDelete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('_action') === 'Encore_Admin_Grid_Actions_Delete' || $request->input('_method') == "PUT") {
            if($request->input('_method') == "PUT"){
                // throw new Exception("لا يمكنك الحذف لان دي نسخه تجريبيه!");
                $message ="لا يمكنك الحذف لان دي نسخه تجريبيه!";
                $error = new MessageBag([
                    'title'   => __(''),
                    'message' => $message,
                ]);
        
                session()->flash('error', $error);
                throw new Exception($message);
            }
            return response()->json([
                'status'  => false,
                'message' => 'لا يمكنك الحذف لان دي نسخه تجريبيه!',
            ], 403);
        }

        return $next($request);
    }
}
