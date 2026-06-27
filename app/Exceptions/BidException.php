<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a bid cannot be placed (auction closed, too low, own auction, …). */
class BidException extends RuntimeException
{
}
