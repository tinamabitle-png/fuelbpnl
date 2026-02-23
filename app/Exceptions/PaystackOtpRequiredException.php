<?php

namespace App\Exceptions;

use Exception;

class PaystackOtpRequiredException extends Exception
{
    public array $context;

    public function __construct(string $message = 'Paystack OTP required.', array $context = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
}

