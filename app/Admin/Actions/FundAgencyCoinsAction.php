<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;

class FundAgencyCoinsAction extends RowAction
{
    public function name()
    {
        return '<i class="fa fa-money text-green" style="margin-right: 5px;"></i> ' . __('Fund Coins');
    }

    public function render()
    {
        $agency = $this->row;
        if (!$agency) {
            return '';
        }

        $agencyId = $agency->id;
        $agencyName = htmlspecialchars($agency->name ?? '', ENT_QUOTES);
        $agencyCoins = (int) ($agency->coins ?? 0);
        $btnTitle = __('Fund Coins');

        return <<<HTML
<a href="javascript:void(0);"
   class="fund-agency-coins-btn text-success"
   data-id="{$agencyId}"
   data-name="{$agencyName}"
   data-coins="{$agencyCoins}"
   title="{$btnTitle}">
    <i class="fa fa-money text-green" style="margin-right: 5px;"></i> {$btnTitle}
</a>
HTML;
    }
}
