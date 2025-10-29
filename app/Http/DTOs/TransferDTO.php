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
}
