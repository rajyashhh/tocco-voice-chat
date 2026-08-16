<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Facades\ManagerHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargesReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id ?? 0,
            'uuid' => $this->user->uuid ?? 0,
            'name' => @$this->name ?: '',
            'target' => $this->target ?? 0,
            'due' => ManagerHelper::getTotalAgenciesSalary($this->managerAgencies, $this->app_id),
        ];
    }
}
