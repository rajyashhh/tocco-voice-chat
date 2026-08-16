<?php

namespace Modules\UsersWallet\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfitLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        [$title, $description] = $this->getTitleAndDescription();

        return [
            'feature_type' => 'profits',
            'type' => $this->type,
            'amount' => $this->amount,
            'title' => $title,
            'description' => $description,
            'negative_sign' => $this->amount < 0,
            'created_at' => Carbon::parse($this->created_at)
                ->locale(app()->getLocale()) // Arabic or English
                ->translatedFormat('d F Y - h:i A'),
        ];
    }

    /**
     * Build title and description based on the log type.
     *
     * @return array{0:string,1:string}
     */
    private function getTitleAndDescription(): array
    {
        $title = '';
        $description = '';

        switch ($this->type) {
            case 'payment':
                $title = __('buy') . '' . $this->amount . ' ' . _('coin');
                $description = _('through') . ' ' . $this->feature;
                break;

            case 'gift':
                $title = __('send gift');
                break;

            case 'exchanges_diamonds':
                $title = __($this->type);
                $description = __('diamond') . abs($this->amount) . ' ' .
                    ' ← ' .  __('coin') . abs($this->coin);
                break;

            case 'gift_room_audio':
            case 'gift_room_live':
                $title = __($this->type);
                $userName = $this->user->name ?? '';
                $description = $userName ? __('from user') . ' ' . $userName : '';
                break;

            case 'moment':
                $title = __($this->type);
                $description = __('your moment');
                break;

            default:
                $title = __($this->type);
                $description = __($this->type);
                break;
        }

        return [$title, $description];
    }
}
