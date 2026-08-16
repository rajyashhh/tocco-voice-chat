<?php

namespace Modules\UsersWallet\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCoinLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        [$title, $description] = $this->getTitleAndDescription();

        return [
            'feature_type'  => $this->feature_type,
            'type'          => $this->type,
            'amount'        => $this->amount,
            'title'         => $title,
            'description'   => $description,
            'negative_sign' => $this->amount < 0,
            'created_at'    => Carbon::parse($this->created_at)
                ->locale(app()->getLocale())
                ->translatedFormat('d F Y - h:i A'),
        ];
    }

    /**
     * Build professional title & description
     */
    private function getTitleAndDescription(): array
    {
        switch ($this->type) {

            case 'payment':
                return [
                    __('wallet.payment_title'),
                    __('wallet.payment_description', [
                        'amount' => abs($this->amount)
                    ]),
                ];

            case 'gifts':
                return [
                    __('wallet.gift_title'),
                    __('wallet.gift_description'),
                ];

            case 'exchanges_diamonds':
                return [
                    __('wallet.exchange_title'),
                    __('wallet.exchange_description', [
                        'diamonds' => abs($this->amount),
                        'coins'    => abs($this->coin),
                    ]),
                ];
            case 'exchange':
                return [
                    __('wallet.exchange_title'),
                    __('wallet.exchange_description', [
                        'diamonds' => abs($this->amount),
                        'coins'    => abs($this->coin),
                    ]),
                ];

            case 'gift_room_audio':
            case 'gift_room_live':
                return [
                    __('wallet.gift_room_title'),
                    __('wallet.gift_room_description', [
                        'user' => $this->user->name ?? __('wallet.unknown_user')
                    ]),
                ];

            case 'moment':
                return [
                    __('wallet.moment_title'),
                    __('wallet.moment_description'),
                ];

            case 'coin_game':
                $amount = abs($this->amount);

                if ($this->amount < 0) {
                    return [
                        __('coin_game.lose_title'),
                        __('coin_game.lose_description', ['amount' => $amount]),
                    ];
                }

                return [
                    __('coin_game.win_title'),
                    __('coin_game.win_description', ['amount' => $amount]),
                ];

            case 'packs':
                return [
                    __('packs.purchase_title'),
                    __('packs.purchase_description', [
                        'amount' => abs($this->amount),
                        'item'   => $this->item_name ?? __('packs.default_item'),
                    ]),
                ];

            case 'room_cup':
                return [
                    __('room_cup_title'),
                    __('room_cup_description', ['amount' => abs($this->amount)]),
                ];

            case 'weekly_star':
                return [
                    __('weekly_star_title'),
                    __('weekly_star_description', ['amount' => abs($this->amount)]),
                ];

            case 'pk_event':
                return [
                    __('pk_event_title'),
                    __('pk_event_description', ['amount' => abs($this->amount)]),
                ];

            case 'charge_event':
                return [
                    __('charge_event_title'),
                    __('charge_event_description', ['amount' => abs($this->amount)]),
                ];

            case 'cashback':
                return [
                    __('cashback_title'),
                    __('cashback_description', ['amount' => abs($this->amount)]),
                ];

            case 'lucky_gift':
                return [
                    __('lucky_gift_title'),
                    __('lucky_gift_description', ['amount' => abs($this->amount)]),
                ];

            case 'vip':
                return [
                    __('vip_title'),
                    __('vip_description', ['amount' => abs($this->amount)]),
                ];

            case 'room_boom':
                return [
                    __('room_boom_title'),
                    __('room_boom_description', ['amount' => abs($this->amount)]),
                ];

            case 'cp':
                return [
                    __('cp_title'),
                    __('cp_description', ['amount' => abs($this->amount)]),
                ];

            case 'create_room':
                return [
                    __('create_room_title'),
                    __('create_room_description', ['amount' => abs($this->amount)]),
                ];
            case 'user':
            case 'agency_owner':
            case 'bd':
                switch ($this->sub_type) {
                    case 'add':
                        return [
                            __('add_title'),
                            __('add_description', ['amount' => abs($this->amount)]),
                        ];
                    case 'transfer':
                        return [
                            __('transfer_title'),
                            __('transfer_description', ['amount' => abs($this->amount)]),
                        ];
                    case 'subtract':
                        return [
                            __('subtract_title'),
                            __('subtract_description', ['amount' => abs($this->amount)]),
                        ];

                    case 'withdrawal_pending':
                        return [
                            __('withdrawal pending'),
                            __('withdrawal_pending', ['amount' => abs($this->amount)]),
                        ];
                        break;
                    default:
                        return [
                            __('general_title'),
                            __('general_description'),
                        ];
                }


            default:
                return [
                    __('wallet.general_title'),
                    __('wallet.general_description'),
                ];
        }
    }
}
