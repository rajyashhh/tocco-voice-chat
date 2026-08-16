<?php

namespace Modules\ServerControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Whatsapp\Entities\WhatsappApp;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;

class ConfigMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !(Auth::user() instanceof WhatsappApp)) {
            return Common::apiResponse(false, __('un_auth'), 401);
        }
        
        $whatsappUser = Auth::user();
        if ($whatsappUser->config == 0) {
            return Common::apiResponse(false, __('api_responses.something_went_wrong'),401 );
        }
        return $next($request);
    }
}
