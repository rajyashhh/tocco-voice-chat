<?php

namespace App\ShippingAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ShippingAgency;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\Auth;

/**
 * Landing dashboard for the Shipping Super Admin portal. The balance shown is the
 * actor's coin balance read straight from admin_users.di — this layer has no
 * dollar wallet and no salary aggregation.
 */
class HomeController extends Controller
{
    public function index(Content $content)
    {
        $me = Auth::user();
        $coins = (int) ($me->di ?? 0);

        $agenciesCount = ShippingAgency::where('country_id', $me->country_id)->count();

        return $content
            ->title(__('Home'))
            ->row(function (Row $row) use ($coins, $agenciesCount) {
                $row->column(6, new InfoBox(__('Coins Balance'), 'diamond', 'green', 'shippingAdmin/wallet', number_format($coins)));
                $row->column(6, new InfoBox(__('Shipping Agencies'), 'truck', 'aqua', 'shippingAdmin/wallet', $agenciesCount));
            });
    }
}