<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a tenant cannot be resolved.
 */
class TenantNotFoundException extends RuntimeException
{
}
