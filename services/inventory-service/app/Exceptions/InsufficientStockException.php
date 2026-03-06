<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when requested inventory quantity is not available.
 */
class InsufficientStockException extends RuntimeException
{
    /**
     * @param  string  $productId  Product UUID that has insufficient stock
     * @param  int     $requested  Quantity requested
     * @param  int     $available  Quantity actually available
     */
    public function __construct(
        private readonly string $productId,
        private readonly int $requested,
        private readonly int $available,
    ) {
        parent::__construct(
            "Insufficient stock for product {$productId}: "
            . "requested {$requested}, available {$available}."
        );
    }

    /** @return string */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /** @return int */
    public function getRequested(): int
    {
        return $this->requested;
    }

    /** @return int */
    public function getAvailable(): int
    {
        return $this->available;
    }
}
