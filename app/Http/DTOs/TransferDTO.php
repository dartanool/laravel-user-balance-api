<?php

namespace App\Http\DTOs;

class TransferDTO
{
    public function __construct(
        public int    $from,
        public int    $to,
        public int    $amount,
        public string $comment = '',
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            from: $data['from_user_id'],
            to: $data['to_user_id'],
            amount: $data['amount'],
            comment: $data['comment'],
        );
    }
}
