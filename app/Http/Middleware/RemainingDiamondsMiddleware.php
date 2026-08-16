<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RemainingDiamondsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $getSetting = function ($key, $default = 0) {
            return \Cache::rememberForever($key, function () use ($key, $default) {
                return Setting::where('key', $key)->value('value') ?? $default;
            });
        };

        $roomBoom = $getSetting('remaining_diamonds_action') ?? 0;
        if (!$roomBoom) {
            // Feature toggled off: render a friendly in-panel notice instead of
            // a bare 403 (the menu entry stays visible, so admins land here).
            $notice = '<div class="box"><div class="box-body" style="padding:40px;text-align:center;">'
                . '<i class="fa fa-diamond" style="font-size:42px;color:#9ca3af;"></i>'
                . '<h4 style="margin-top:18px;">' . e(__('remaining diamonds feature disabled')) . '</h4>'
                . '<p style="color:#6b7280;">' . e(__('remaining diamonds feature disabled hint')) . '</p>'
                . '<a href="' . admin_url('agency-settings') . '" class="btn btn-primary" style="margin-top:10px;">'
                . e(__('Agency Settings')) . '</a>'
                . '</div></div>';

            $content = (new Content())
                ->title(__('Remaining Diamonds'))
                ->body($notice);

            return response($content);
        }

        return $next($request);
    }
}
