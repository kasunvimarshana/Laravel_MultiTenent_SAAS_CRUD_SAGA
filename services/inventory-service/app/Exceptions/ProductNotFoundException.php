<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a requested product cannot be found. */
class ProductNotFoundException extends RuntimeException
{
    public function __construct(string $productId)
    {
        parent::__construct("Product not found: {$productId}.");
    }
}
