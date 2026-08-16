<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestTakeSalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'user' => [
                'id' => $this->user->id ?? 0,
                'name' => $this->user->name ?? '',
            ],
            'amount' => $this->amount,
            'phone' => $this->phone ?? '',
            'gmail' => $this->gmail ?? '',
            'country' => $this->country ?? '',
            'status' => $this->status,
            'created_at' => $this->created_at,
            'Payment_method' => $this->getWithdrawFields(),

        ];
    }

    private function getWithdrawFields()
    {
        $data = @$this->paymentWithDraw?->userWithdrawFields->where("user_id", $this->user_id);
        if (!$data) [];
        $results = [];

        if ($data) {
            foreach ($data as $da) {
                $key = $da->payment_withdraw_field->name ?? null;
                $value = $da->value ?? null;

                if ($key) {
                    $results[$key] = $value;
                }
            }
        }

        return $results;
    }
}
