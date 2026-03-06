<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an order cannot be found.
 */
class OrderNotFoundException extends RuntimeException
{
}
