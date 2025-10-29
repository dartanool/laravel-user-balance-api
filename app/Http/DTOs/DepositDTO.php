<?php

namespace App\Http\DTOs;

class DepositDTO
{
    public function __construct(
        public int    $id,
        public float  $amount,
        public string $comment = '',
    )
    {
    }
}
