<?php

namespace App\Http\DTOs;

class WithdrawDTO
{
    public function __construct(
        public int    $id,
        public float  $amount,
        public string $comment = '',
    )
    {
    }
}
