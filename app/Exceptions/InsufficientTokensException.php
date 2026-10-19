<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientTokensException extends RuntimeException
{
    public function __construct(int $required, int $available)
    {
        parent::__construct(
            "Token tidak cukup. Dibutuhkan {$required} token, saldo saat ini {$available} token.",
        );
    }
}
