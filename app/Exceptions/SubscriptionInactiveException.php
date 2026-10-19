<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionInactiveException extends RuntimeException
{
    public function __construct(string $status)
    {
        parent::__construct(
            "Langganan tidak aktif (status: {$status}). Fitur berbayar diblokir sampai topup langganan disetujui di halaman Billing.",
        );
    }
}
