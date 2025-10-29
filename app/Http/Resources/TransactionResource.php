<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'user_id' => $this->user_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'comment' => $this->comment,
        ];
    }
}
