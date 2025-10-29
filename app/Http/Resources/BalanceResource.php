<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'user_id' => $this->user_id ?? $this->id ?? null,
            'balance' => (float) ($this->amount ?? 0.00),
        ];
    }
}
