<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;

class RemoveAgencyCoinsAction extends RowAction
{
    public function name()
    {
        return '<i class="fa fa-minus-circle text-red" style="margin-right: 5px;"></i> ' . __('Remove Coins');
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
        $btnTitle = __('Remove Coins');

        return <<<HTML
<a href="javascript:void(0);"
   class="remove-agency-coins-btn text-danger"
   data-id="{$agencyId}"
   data-name="{$agencyName}"
   data-coins="{$agencyCoins}"
   title="{$btnTitle}">
    <i class="fa fa-minus-circle text-red" style="margin-right: 5px;"></i> {$btnTitle}
</a>
HTML;
    }
}
