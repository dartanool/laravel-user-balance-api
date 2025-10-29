<?php

namespace App\Http\DTOs;

class DepositDTO
{
    public function __construct(
        public int    $userId,
        public float  $amount,
        public string $comment = '',
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            amount: $data['amount'],
            comment: $data['comment'],
        );
    }
}
