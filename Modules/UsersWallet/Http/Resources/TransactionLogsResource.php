<?php

namespace Modules\UsersWallet\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class TransactionLogsResource extends JsonResource
{
     /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $title = '';
        switch ($this->operation) {
            case 'add':
                if ($this->type === 'user') {
                    $title = __('add_user_profit', ['target' => $this->related ? $this->related->name : '']);
                } elseif ($this->type === 'agency_owner') {
                    $title = __('add_agency_profit', ['target' => $this->related ? $this->related->name : '']);
                } elseif ($this->type === 'bd') {
                    $title = __('add_bd_profit', ['admin' => $this->admin ? $this->admin->name : '']);
                } else {
                    $title = __('add', ['target' => $this->related ? $this->related->name : '']);
                }
                break;

            case 'transfer':
                if ($this->amount > 0) {
                    $title = __('transfer_to', ['target' => $this->related ? $this->related->name : '']);
                } else {
                    $title = __('transfer_from', ['target' => $this->related ? $this->related->name : '']);
                }
                break;

            case 'subtract':
                $title = __('withdraw', ['admin' => $this->admin ? $this->admin->name : '']);
                break;
            case 'withdrawal_pending':
                $title = __('withdrawal pending');
                break;


            default:
                $title = '';
        }

        return [
            'title'         => $title,
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'amount'        => $this->amount,
            'operation'     => $this->operation,
            'type'          => $this->type,
            'before_amount' => $this->before_amount,
            'created_at'    => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
      
       ];
    }
}
