<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    protected $message = 'Not enough credits to submit an offer.';
}
