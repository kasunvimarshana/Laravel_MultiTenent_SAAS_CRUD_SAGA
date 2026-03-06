<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an invalid state transition is attempted on an order.
 */
class OrderStateException extends RuntimeException
{
}
